#20170603
#Flora Tsai

import boto3
import json
from boto3.dynamodb.conditions import Key, Attr

#variables
region = 'us-east-1'
sqs_queue_name = '<username>_archive_retrieval'
vault = 'ucmpcs'
results_bucket = 'gas-results'
dbtable_name = '<username>_annotations'

# Connect to SQS and get the message queue
sqs = boto3.resource('sqs', region_name=region)
queue = sqs.get_queue_by_name(QueueName=sqs_queue_name)

#glacier
glacier = boto3.client('glacier', region_name=region)

#dynamoDB
dynamoDB = boto3.resource('dynamodb', region_name=region)
ann_table = dynamoDB.Table(dbtable_name)

#s3
s3 = boto3.resource('s3')

'''
get file from glacier
https://boto3.readthedocs.io/en/latest/reference/services/glacier.html#Glacier.Client.get_job_output
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
			job_id = msg_body['JobId']
			archive_id = msg_body['ArchiveId']
			# We've put username in job description previously
			username = msg_body['JobDescription']
			obj = glacier.get_job_output(vaultName=vault, jobId=job_id)
			# get s3_key_result_file
			db_response = ann_table.query(IndexName='username_index', KeyConditionExpression=Key('username').eq(username),
											FilterExpression=Attr('results_file_archive_id').eq(archive_id))
			s3_key_result_file = db_response['Items'][0]['s3_key_result_file']
			s3.Object(bucket_name=results_bucket, key=s3_key_result_file).upload_fileobj(obj['body'])
			print('success')
			
			message.delete()

