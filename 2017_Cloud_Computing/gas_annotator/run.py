# Copyright (C) 2011-2016 Vas Vasiliadis
# University of Chicago
##
__author__ = 'Vas Vasiliadis <vas@uchicago.edu>'

import sys
import time
import driver

#20170526
#Flora Tsai

import boto3
import subprocess
import time
from boto3.dynamodb.conditions import Key, Attr
import json

bucket = "gas-results"
prefix = "<username>/"
region = 'us-east-1'
dbtable_name = '<username>_annotations'
topic_arn = 'arn:aws:sns:'

dynamoDB = boto3.resource('dynamodb', region_name=region)
ann_table = dynamoDB.Table(dbtable_name)

# A rudimentary timer for coarse-grained profiling
class Timer(object):
	def __init__(self, verbose=False):
		self.verbose = verbose

	def __enter__(self):
		self.start = time.time()
		return self

	def __exit__(self, *args):
		self.end = time.time()
		self.secs = self.end - self.start
		self.msecs = self.secs * 1000  # millisecs
		if self.verbose:
			print "Elapsed time: %f ms" % self.msecs

if __name__ == '__main__':
	# Call the AnnTools pipeline
	if len(sys.argv) > 1:
		input_file_name = sys.argv[1]
		with Timer() as t:
			driver.run(input_file_name, 'vcf')
		print "Total runtime: %s seconds" % t.secs

		job_id = sys.argv[2]
		response = ann_table.query(KeyConditionExpression=Key('job_id').eq(job_id))
		username = response['Items'][0]['username']

		# Add code here to save results and log files to S3 results bucket
		s3 = boto3.resource('s3')

		# Upload the results file
		results_file = "." + input_file_name.split('.')[1] + ".annot.vcf"
		# print results_file
		key = prefix + username + '/' + results_file.split('/')[-1]
		# print key
		s3.Object(bucket_name=bucket, key=key).upload_file(Filename=results_file)

		# Upload the log file
		log_file = input_file_name + ".count.log"
		# print log_file
		key2 = prefix + username + '/' + log_file.split('/')[-1]
		# print key2
		s3.Object(bucket_name=bucket, key=key2).upload_file(Filename=log_file)
		
		# Clean up (delete) local job files
		subprocess.Popen(['rm', '-r', './data/' + job_id])
		
		values = {':results_bucket': bucket,
				  ':result_key': key,
				  ':log_key': key2,
				  ':complete_time': int(time.time()),
				  ':status': "COMPLETED"}
		ann_table.update_item(Key={'job_id': job_id}, 
							  UpdateExpression="set s3_results_bucket = :results_bucket, s3_key_result_file = :result_key, s3_key_log_file = :log_key, complete_time = :complete_time, job_status = :status",
							  ExpressionAttributeValues=values)

		data = {"job_id":job_id,
				"username": username}
		sns = boto3.resource('sns', region_name=region)
		topic = sns.Topic(topic_arn)
		topic.publish(Message=json.dumps(data))
	else:
		print 'A valid .vcf file must be provided as input to this program.'
