#20170603
#Flora Tsai

import boto3
import json
import cork
import calendar
from datetime import datetime
from botocore.exceptions import ClientError
from boto3.dynamodb.conditions import Key, Attr

#variables
region = 'us-east-1'
sqs_queue_name = '<username>_archive_check'
vault = 'ucmpcs'
results_bucket = 'gas-results'
dbtable_name = '<username>_annotations'
db_url = ''
topic_arn = 'arn:aws:sns:us-east-1:127134666975:<username>_archive_retry'

# Set up the conenction to the auth database
auth_db = cork.sqlalchemy_backend.SqlAlchemyBackend(
	db_full_url=db_url, users_tname='users', roles_tname='roles', pending_reg_tname='register', initialize=False)
auth = cork.Cork(backend=auth_db)

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

# Poll the message queue in a loop
while True:
	# current time in epoch time representation
	cur_time = calendar.timegm(datetime.utcnow().timetuple())
	# Attempt to read a message from the queue
    # Use long polling - DO NOT use sleep() to wait between polls
	messages = queue.receive_messages(WaitTimeSeconds=20)
	print(len(messages))
	# If no message was read, continue polling loop
	if len(messages) > 0:
		for message in messages:
			# If a message was read, extract job parameters from the message body
			msg_body = json.loads(json.loads(message.body)['Message'])
			job_id = msg_body['job_id']
			print(job_id)
			db_response = ann_table.query(KeyConditionExpression=Key('job_id').eq(job_id))
			item = db_response['Items'][0]
			job_status = item['job_status']
			
			#double check
			if job_status != 'COMPLETED':
				message.delete()
				continue

			complete_time = item['complete_time']
			username = msg_body['username']
			user = auth.user(username)
			role = user.role
			if role =='free_user':
				#over 30 mins, free_user's file archived to glacier
				if (complete_time + 1800) < cur_time:
					s3_key_result_file = item['s3_key_result_file']
					body = 'https://s3.amazonaws.com/' + results_bucket + '/' + s3_key_result_file
					response = glacier.upload_archive(vaultName=vault, body=body) 

					#update ann_table
					try:
						ann_table.update_item(Key={'job_id': job_id},
												UpdateExpression='SET #archiveId = :id',
												ExpressionAttributeNames={'#archiveId': 'results_file_archive_id'},
												ExpressionAttributeValues={':id': response['archiveId']})
					except ClientError as e:
							print(e.response['Error']['Message'])

					# delete object in s3
					object = s3.Object(results_bucket, s3_key_result_file)
					response_2 = object.delete()
				# give the message a second chance, resend it back to queue
				else:
					sns = boto3.resource('sns', region_name=region)
					topic = sns.Topic(topic_arn)
					topic.publish(Message=json.dumps(msg_body))

			message.delete()