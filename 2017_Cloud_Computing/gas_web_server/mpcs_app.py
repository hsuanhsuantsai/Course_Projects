# mpcs_app.py
#
# Copyright (C) 2011-2017 Vas Vasiliadis
# University of Chicago
#
# Application logic for the GAS
#
##
__author__ = 'Vas Vasiliadis <vas@uchicago.edu>'

import base64
import datetime
import hashlib
import hmac
import json
import sha
import string
import time
import urllib
import urlparse
import uuid
import boto3
import botocore.session
import calendar
from boto3.dynamodb.conditions import Key

from mpcs_utils import log, auth
from bottle import route, request, redirect, template, static_file, response

'''
*******************************************************************************
Set up static resource handler - DO NOT CHANGE THIS METHOD IN ANY WAY
*******************************************************************************
'''
@route('/static/<filename:path>', method='GET', name="static")
def serve_static(filename):
  # Tell Bottle where static files should be served from
  return static_file(filename, root=request.app.config['mpcs.env.static_root'])

'''
*******************************************************************************
Home page
*******************************************************************************
'''
@route('/', method='GET', name="home")
def home_page():
  log.info(request.url)
  return template(request.app.config['mpcs.env.templates'] + 'home', auth=auth)

'''
*******************************************************************************
Registration form
*******************************************************************************
'''
@route('/register', method='GET', name="register")
def register():
  log.info(request.url)
  return template(request.app.config['mpcs.env.templates'] + 'register',
    auth=auth, name="", email="", username="", 
    alert=False, success=True, error_message=None)

@route('/register', method='POST', name="register_submit")
def register_submit():
  try:
    auth.register(description=request.POST.get('name').strip(),
                  username=request.POST.get('username').strip(),
                  password=request.POST.get('password').strip(),
                  email_addr=request.POST.get('email_address').strip(),
                  role="free_user")
  except Exception, error:
    return template(request.app.config['mpcs.env.templates'] + 'register', 
      auth=auth, alert=True, success=False, error_message=error)  

  return template(request.app.config['mpcs.env.templates'] + 'register', 
    auth=auth, alert=True, success=True, error_message=None)

@route('/register/<reg_code>', method='GET', name="register_confirm")
def register_confirm(reg_code):
  log.info(request.url)
  try:
    auth.validate_registration(reg_code)
  except Exception, error:
    return template(request.app.config['mpcs.env.templates'] + 'register_confirm',
      auth=auth, success=False, error_message=error)

  return template(request.app.config['mpcs.env.templates'] + 'register_confirm',
    auth=auth, success=True, error_message=None)

'''
*******************************************************************************
Login, logout, and password reset forms
*******************************************************************************
'''
@route('/login', method='GET', name="login")
def login():
  log.info(request.url)
  redirect_url = "/annotations"
  # If the user is trying to access a protected URL, go there after auhtenticating
  if request.query.redirect_url.strip() != "":
    redirect_url = request.query.redirect_url

  return template(request.app.config['mpcs.env.templates'] + 'login', 
    auth=auth, redirect_url=redirect_url, alert=False)

@route('/login', method='POST', name="login_submit")
def login_submit():
  auth.login(request.POST.get('username'),
             request.POST.get('password'),
             success_redirect=request.POST.get('redirect_url'),
             fail_redirect='/login')

@route('/logout', method='GET', name="logout")
def logout():
  log.info(request.url)
  auth.logout(success_redirect='/login')


'''
*******************************************************************************
*
CORE APPLICATION CODE IS BELOW...
*
*******************************************************************************
'''

'''
*******************************************************************************
Subscription management handlers
*******************************************************************************
'''
'''
Create subscription
https://stripe.com/docs/api#create_subscription
https://gist.github.com/maccman/3299715
https://stripe.com/docs/subscriptions/quickstart
https://stripe.com/docs/api#create_customer
'''
import stripe

# Display form to get subscriber credit card info
@route('/subscribe', method='GET', name="subscribe")
def subscribe():
  auth.require(fail_redirect='/login?redirect_url=' + request.url)
  return template(request.app.config['mpcs.env.templates'] + 'subscribe',
    auth=auth)

# Process the subscription request
@route('/subscribe', method='POST', name="subscribe_submit")
def subscribe_submit():
  stripe.api_key = request.app.config['mpcs.stripe.secret_key']
  stripe_token = request.forms.get('stripe_token')
  # print stripe_token
  # print type(stripe_token)
  customer = stripe.Customer.create(source=stripe_token, email=auth.current_user.email_addr, description=auth.current_user.username)
  # print customer

  #initiate the archive restoration job
  region_name = request.app.config['mpcs.aws.app_region']
  dynamoDB = boto3.resource('dynamodb', region_name=region_name)
  dbtable_name = request.app.config['mpcs.aws.dynamodb.annotations_table']
  ann_table = dynamoDB.Table(dbtable_name)
  username = auth.current_user.username
  db_response = ann_table.query(IndexName='username_index', KeyConditionExpression=Key('username').eq(username))
  glacier = boto3.client('glacier', region_name=region_name)
  for i in db_response['Items']:
    print(i)
    # make sure we have archive_id
    if i['job_status'] == 'COMPLETED' and (i['complete_time'] + 1800) < calendar.timegm(datetime.datetime.utcnow().timetuple()):
      Params = {'Type': 'archive-retrieval', 'ArchiveId': i['results_file_archive_id'], 'Description': username,
                'SNSTopic': request.app.config['mpcs.aws.sns.archive_retrieval_topic'], 'Tier': 'Expedited'}
      glacier.initiate_job(vaultName=request.app.config['mpcs.aws.glacier.vault'], jobParameters=Params)

  auth.current_user.update(role="premium_user")
  # print auth.current_user.role
  st_response = stripe.Subscription.create(customer=customer.id, plan="premium_plan")
  # print st_response
  return template(request.app.config['mpcs.env.templates'] + 'subscribe_confirm',
    auth=auth, stripe_id=st_response.id)


'''
*******************************************************************************
Display the user's profile with subscription link for Free users
*******************************************************************************
'''
@route('/profile', method='GET', name="profile")
def user_profile():
  auth.require(fail_redirect='/login?redirect_url=' + request.url)
  return template(request.app.config['mpcs.env.templates'] + 'profile',
    auth=auth)


'''
*******************************************************************************
Creates the necessary AWS S3 policy document and renders a form for
uploading an input file using the policy document
*******************************************************************************
'''
'''
content-length-range in policy
http://docs.aws.amazon.com/AmazonS3/latest/API/sigv4-HTTPPOSTConstructPolicy.html#sigv4-ConditionMatching
'''
@route('/annotate', method='GET', name="annotate")
def upload_input_file():
  log.info(request.url)

  # Check that user is authenticated
  auth.require(fail_redirect='/login?redirect_url=' + request.url)

  # Use the boto session object only to get AWS credentials
  session = botocore.session.get_session()
  aws_access_key_id = str(session.get_credentials().access_key)
  aws_secret_access_key = str(session.get_credentials().secret_key)
  aws_session_token = str(session.get_credentials().token)

  # Define policy conditions
  bucket_name = request.app.config['mpcs.aws.s3.inputs_bucket']
  encryption = request.app.config['mpcs.aws.s3.encryption']
  acl = request.app.config['mpcs.aws.s3.acl']

  # Generate unique ID to be used as S3 key (name)
  key_name = request.app.config['mpcs.aws.s3.key_prefix'] + auth.current_user.username + '/' + str(uuid.uuid4())

  # Redirect to a route that will call the annotator
  redirect_url = str(request.url) + "/job"

  # Define the S3 policy doc to allow upload via form POST
  # The only required elements are "expiration", and "conditions"
  # must include "bucket", "key" and "acl"; other elements optional
  # NOTE: We also must inlcude "x-amz-security-token" since we're
  # using temporary credentials via instance roles
  policy_document = ''
  #policy dependent on user role
  if (auth.current_user.role == 'free_user'):
    policy_document = str({
      "expiration": (datetime.datetime.utcnow() + 
        datetime.timedelta(hours=24)).strftime("%Y-%m-%dT%H:%M:%SZ"),
      "conditions": [
        {"bucket": bucket_name},
        ["starts-with","$key", key_name],
        ["starts-with", "$success_action_redirect", redirect_url],
        {"x-amz-server-side-encryption": encryption},
        {"x-amz-security-token": aws_session_token},
        {"acl": acl},
        ["content-length-range", 0, 1024*150]]})
  else:
    policy_document = str({
      "expiration": (datetime.datetime.utcnow() + 
        datetime.timedelta(hours=24)).strftime("%Y-%m-%dT%H:%M:%SZ"),
      "conditions": [
        {"bucket": bucket_name},
        ["starts-with","$key", key_name],
        ["starts-with", "$success_action_redirect", redirect_url],
        {"x-amz-server-side-encryption": encryption},
        {"x-amz-security-token": aws_session_token},
        {"acl": acl}]})

  # Encode the policy document - ensure no whitespace before encoding
  policy = base64.b64encode(policy_document.translate(None, string.whitespace))

  # Sign the policy document using the AWS secret key
  signature = base64.b64encode(hmac.new(aws_secret_access_key, policy, hashlib.sha1).digest())

  # Render the upload form
  # Must pass template variables for _all_ the policy elements
  # (in addition to the AWS access key and signed policy from above)
  return template(request.app.config['mpcs.env.templates'] + 'upload',
    auth=auth, bucket_name=bucket_name, s3_key_name=key_name,
    aws_access_key_id=aws_access_key_id,     
    aws_session_token=aws_session_token, redirect_url=redirect_url,
    encryption=encryption, acl=acl, policy=policy, signature=signature)


'''
*******************************************************************************
Accepts the S3 redirect GET request, parses it to extract 
required info, saves a job item to the database, and then
publishes a notification for the annotator service.
*******************************************************************************
'''
@route('/annotate/job', method='GET')
def create_annotation_job_request():
  # Check that user is authenticated
  auth.require(fail_redirect='/login?redirect_url=' + request.url)

  # Get bucket name, key, and job ID from the S3 redirect URL
  my_query = request.query
  bucket = my_query.bucket
  s3_key = my_query.key
  # username = s3_key.split('/')[1]
  username = auth.current_user.username
  file_name = s3_key.split('/')[-1]
  input_file_name = file_name.split('~')[-1]
  job_id = file_name.split('~')[0]
  
  # Create a job item and persist it to the annotations database
  data = {"job_id": job_id,
      "username": username,
      "input_file_name": input_file_name,
      "s3_inputs_bucket": bucket,
      "s3_key_input_file": s3_key,
      "submit_time": int(time.time()),
      "job_status": "PENDING"
      }
  
  region_name = request.app.config['mpcs.aws.app_region']
  dynamoDB = boto3.resource('dynamodb', region_name=region_name)
  dbtable_name = request.app.config['mpcs.aws.dynamodb.annotations_table']
  ann_table = dynamoDB.Table(dbtable_name)
  ann_table.put_item(Item=data)

  sns = boto3.resource('sns', region_name=region_name)
  topic_arn = request.app.config['mpcs.aws.sns.job_request_topic']
  topic = sns.Topic(topic_arn)
  topic.publish(Message=json.dumps(data))
  return json.dumps({"code": response.status, "data": {"job_id": job_id, "input-file": input_file_name}})


'''
*******************************************************************************
List all annotations for the user
*******************************************************************************
'''
'''
dynamoDB query
http://stackoverflow.com/questions/35758924/how-do-we-query-on-a-secondary-index-of-dynamodb-using-boto3
'''
@route('/annotations', method='GET', name="annotations_list")
def get_annotations_list():
  # Check that user is authenticated
  auth.require(fail_redirect='/login?redirect_url=' + request.url)

  region_name = request.app.config['mpcs.aws.app_region']
  dynamoDB = boto3.resource('dynamodb', region_name=region_name)
  dbtable_name = request.app.config['mpcs.aws.dynamodb.annotations_table']
  ann_table = dynamoDB.Table(dbtable_name)
  username = auth.current_user.username
  # print dbtable_name
  # print username
  response = ann_table.query(IndexName='username_index', KeyConditionExpression=Key('username').eq(username))
  list = []
  for i in response['Items']:
    tmp = []
    tmp.append(i['job_id'])
    tmp.append(time.strftime('%Y-%m-%d %H:%M',time.gmtime(i['submit_time'])))
    tmp.append(i['input_file_name'])
    tmp.append(i['job_status'])
    list.append(tmp)

  return template(request.app.config['mpcs.env.templates'] + 'annotation_list', auth=auth, list=list)


'''
*******************************************************************************
Display details of a specific annotation job
*******************************************************************************
'''
'''
Convert epoch time to datetime
http://stackoverflow.com/questions/12400256/python-converting-epoch-time-into-the-datetime
pre-signed URL
https://stackoverflow.com/questions/33549254/how-to-generate-url-from-boto3-in-amazon-web-services
http://boto3.readthedocs.io/en/latest/reference/services/s3.html#S3.Client.generate_presigned_url
'''
@route('/annotations/<job_id>', method='GET', name="annotation_details")
def get_annotation_details(job_id):
  # Check that user is authenticated
  auth.require(fail_redirect='/login?redirect_url=' + request.url)

  region_name = request.app.config['mpcs.aws.app_region']
  dynamoDB = boto3.resource('dynamodb', region_name=region_name)
  dbtable_name = request.app.config['mpcs.aws.dynamodb.annotations_table']
  ann_table = dynamoDB.Table(dbtable_name)
  response = ann_table.query(KeyConditionExpression=Key('job_id').eq(job_id))
  for i in response['Items']:
    if i['username'] != auth.current_user.username:
      return "Not authorized to view this job"
    # print(i['job_id'])
    job_id = i['job_id']
    # print(i['job_status'])
    job_status = i['job_status']
    # print(time.strftime('%Y-%m-%d %H:%M',time.gmtime(i['submit_time'])))
    submit_time = time.strftime('%Y-%m-%d %H:%M',time.gmtime(i['submit_time']))
    # print(i['input_file_name'])
    input_file_name = i['input_file_name']
    s3_key_input_file = i['s3_key_input_file']

    # create pre-signed url for input file
    client = boto3.client('s3')
    input_Params = {"Bucket": request.app.config['mpcs.aws.s3.inputs_bucket'], "Key": s3_key_input_file}
    input_url = client.generate_presigned_url('get_object', Params=input_Params)
    complete_time = ''
    signed_url = ''
    #indicate if complete time exceeds 30 mins
    timeout = False

    if job_status == 'COMPLETED':
      # print(time.strftime('%Y-%m-%d %H:%M',time.gmtime(i['complete_time'])))
      complete_time = time.strftime('%Y-%m-%d %H:%M',time.gmtime(i['complete_time']))
      s3_key_result_file = i['s3_key_result_file']
      Params = {"Bucket": request.app.config['mpcs.aws.s3.results_bucket'], "Key": s3_key_result_file}
      signed_url = client.generate_presigned_url('get_object', Params=Params)
      current_time = calendar.timegm(datetime.datetime.utcnow().timetuple())
      timeout = current_time > (i['complete_time'] + 1800)
    return template(request.app.config['mpcs.env.templates'] + 'details', auth=auth, job_id=job_id,
                    submit_time=submit_time, input_file_name=input_file_name, job_status=job_status,
                    complete_time=complete_time, input_url=input_url, signed_url=signed_url, timeout=timeout)
  

'''
*******************************************************************************
Display the log file for an annotation job
*******************************************************************************
'''
'''
Read object body into string
http://stackoverflow.com/questions/31976273/open-s3-object-as-a-string-with-boto3
'''
@route('/annotations/<job_id>/log', method='GET', name="annotation_log")
def view_annotation_log(job_id):
  # Check that user is authenticated
  auth.require(fail_redirect='/login?redirect_url=' + request.url)
  
  region_name = request.app.config['mpcs.aws.app_region']
  dynamoDB = boto3.resource('dynamodb', region_name=region_name)
  dbtable_name = request.app.config['mpcs.aws.dynamodb.annotations_table']
  ann_table = dynamoDB.Table(dbtable_name)
  response = ann_table.query(KeyConditionExpression=Key('job_id').eq(job_id))
  key = ''
  for i in response['Items']:
    if i['username'] != auth.current_user.username:
      return "Not authorized to view this job"
    key = i['s3_key_log_file']

  s3 = boto3.resource('s3')
  object = s3.Object(bucket_name=request.app.config['mpcs.aws.s3.results_bucket'], key=key)
  log_res = object.get()['Body'].read().decode('utf-8')
  return template(request.app.config['mpcs.env.templates'] + 'job_log', auth=auth, log_res=log_res)


### EOF
