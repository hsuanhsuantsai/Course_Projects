#20170605
#Flora Tsai

import boto3
import json
import subprocess
import time
from botocore.exceptions import ClientError

#variables
dbtable_name = "<username>_annotations"
sqs_queue_name = "<username>_job_requests"
region = 'us-east-1'

# Connect to SQS and get the message queue
sqs = boto3.resource('sqs', region_name=region)
queue = sqs.get_queue_by_name(QueueName=sqs_queue_name)

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
			bucket = msg_body['s3_inputs_bucket']
			s3_key = msg_body['s3_key_input_file']

			file_name = s3_key.split('/')[-1]
	
			# Get the input file S3 object and copy it to a local file
			my_dir = './data/' + job_id
			file_to_run = my_dir + '/' + file_name
			subprocess.Popen(['mkdir', my_dir])
			s3 = boto3.resource('s3')
			s3.Object(bucket_name=bucket, key=s3_key).download_file(Filename=file_to_run)

			# Launch annotation job as a background process
	
			# Change job status
			# if succeeds, then run the job, otherwise do nothing
			dynamoDB = boto3.resource('dynamodb', region_name=region)
			ann_table = dynamoDB.Table(dbtable_name)
			try:
				ann_table.update_item(Key={'job_id': job_id},
									  UpdateExpression="set job_status = :status",
									  ConditionExpression="job_status = :str",
									  ExpressionAttributeValues={':status': "RUNNING", ':str': "PENDING"})
			except ClientError as e:
				if e.response['Error']['Code'] == "ConditionalCheckFailedException":
					print(e.response['Error']['Message'])
				#if other error, sleep for 10 seconds as buffer
				else:
					print(e.response['Error']['Message'])
					time.sleep(10)
			else:
				ann_process = subprocess.Popen(['python', './run.py', file_to_run, job_id])

			message.delete()
