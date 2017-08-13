# GAS (	Genomics Annotation Service ) Cloud App
* Established a fully functional and scalable SaaS cloud application in Python combined with front-end web design and back-end data processing for genomics annotation  
* Shaped the app by at least 6 Amazon Web Services and integrated it with Stripe, a third-party SaaS system, for subscription management and billing functions  

## Codes
* gas_web_server  
		A front-end web application for users to interact with GAS
* gas_annotator  
		A server running the AnnTools software for annotation
* utilities  
		Utilities functions
* auto_scaling_user_data_web_server.txt  
		User data for gas_web_server auto-scaling
* auto_scaling_user_data_annotator.txt  
		User data for gas_annotator auto-scaling
* annotator_test.py  
		Test script for gas_annotator auto-scaling load test
* locustfile.py  
		Test script for gas_web_server auto-scaling, which runs on Locust instance

