#20170526
#Flora Tsai

import boto3
import json
import cork

#variables
dbtable_name = "<username>_annotations"
sqs_queue_name = "<username>_results_notifications"
region = 'us-east-1'
email_sender = "<username>@ucmpcs.org"
website = "https://<username>.ucmpcs.org"
db_url = ''
smtp_url = ''

'''
Use cork to get data from database
http://cork.firelet.net/cork_module.html
'''
# Set up the conenction to the auth database
auth_db = cork.sqlalchemy_backend.SqlAlchemyBackend(
	db_full_url=db_url, users_tname='users', roles_tname='roles', pending_reg_tname='register', initialize=False)

# Instantiate an authn/authz provider
auth = cork.Cork(backend=auth_db, email_sender=email_sender, smtp_url=smtp_url)

# Connect to SQS and get the message queue
sqs = boto3.resource('sqs', region_name=region)
queue = sqs.get_queue_by_name(QueueName=sqs_queue_name)

ses_client = boto3.client('ses', region_name=region)

'''
send email to user
https://boto3.readthedocs.io/en/latest/reference/services/ses.html#SES.Client.send_email
'''

# Poll the message queue in a loop
while True:
	# Attempt to read a message from the queue
    # Use long polling - DO NOT use sleep() to wait between polls
	messages = queue.receive_messages(WaitTimeSeconds=20)

	# If no message was read, continue polling loop
	if len(messages) > 0:
		for message in messages:
			# If a message was read, extract job parameters from the message body
			msg_body = json.loads(json.loads(message.body)['Message'])
			job_id = msg_body['job_id']
			username = msg_body['username']
			user = auth.user(username)
			user_email = user.email_addr

			dest = {'ToAddresses': [user_email]}
			content = {'Data': 'Job ID:' + job_id + '\nView details here:' + website + '/annotations/' + job_id}
			msg = {'Subject': {'Data':'Your job is complete.'}, 
					'Body': {'Text': content, 'Html': content}}
			response = ses_client.send_email(Source=email_sender, Destination=dest, Message=msg)
			message.delete()
