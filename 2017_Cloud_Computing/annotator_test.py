#20170605
#Flora Tsai

import boto3
import json
import time

sns = boto3.resource('sns')
topic_arn = 'arn:aws:sns:'
topic = sns.Topic(topic_arn)


# Connect to SQS
sqs = boto3.resource('sqs')
queue = sqs.get_queue_by_name(QueueName='<username>_job_requests')

#large file
data_1 = {"job_id": 'a893ed94-3393-11e7-8a97-0ac17f7c6ad2',
			"username": 'userX',
			"input_file_name": 'premium_1.vcf',
			"s3_inputs_bucket": 'gas-inputs',
			"s3_key_input_file": '<username>/userX/a893ed94-3393-11e7-8a97-0ac17f7c6ad2~premium_1.vcf',
			"submit_time": 1494209736,
			"job_status": "COMPLETED"}
# small file, premium user
# premium user: in case any message goes to archive_check queue
data_2 = {"job_id": '7a258d34-b340-4654-a9c6-8a87dde2bbea',
			"username": 'userY',
			"input_file_name": 'test.vcf',
			"s3_inputs_bucket": 'gas-inputs',
			"s3_key_input_file": '<username>/userY/7a258d34-b340-4654-a9c6-8a87dde2bbea~test.vcf',
			"submit_time": 1496557739,
			"job_status": "COMPLETED"}

# body = json.dumps(data_1)
# body = json.dumps(data_2)
while True:
	#send message every 5 seconds
	# queue.send_message(MessageBody=body, DelaySeconds=5)
	topic.publish(Message=json.dumps(data_2))
	print(1)
	# time.sleep(3)
