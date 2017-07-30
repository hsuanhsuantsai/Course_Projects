//20170130
//Flora Tsai

//main program

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include "mnist.h"
#include "distance.h"
#include "knn.h"

#define SCHEMELENGTH 255

//train, N, test, k, schemename
int main(int argc, char *argv[]){
	int s=0;	//# of schemes provided
	char *L=LIST(&s);
	if (argc != 6){
		printf("Usage: ./ocr [train-name] [train-size] [test-name] [k] [distance-scheme]\n");
		printf("The following distance schemes are support:%s\n",L);
		return 1;
	}
	char schemes_list[s][SCHEMELENGTH];
	char str[SCHEMELENGTH];
	int l_i=0;		//index of schemes_list
	int s_i = 0;	//index of str
	for (int i=0; i<strlen(L); i++){
		if (isblank(L[i])){
			str[s_i] = '\0';
			strcpy(schemes_list[l_i++],str);
			s_i=0;
			continue;
		}
		str[s_i++]=L[i];
	}
	str[s_i] = '\0';
	strcpy(schemes_list[l_i++],str);
	// for (int i=0; i<s; i++)
	// 	printf("%s\n", schemes_list[i]);

	int all_train_size[]={25,50,75,100};
	int all_k[]={1,5,10,15,20};
	char *train = argv[1];
	char *test = argv[3];
	char *schemename = argv[5];
	mnist_dataset_handle train_h = mnist_open(train);
	mnist_dataset_handle test_h = mnist_open(test);
	int train_size = mnist_image_count(train_h);
	char *end;
	int N = 0;
	int k = 0;
	
	mnist_dataset_handle sample25_h = NULL;
	mnist_dataset_handle sample50_h = NULL;
	mnist_dataset_handle sample75_h = NULL;

	if (!strcmp(argv[2],"all")){	//N is all
		for (int i=0; i<3; i++){
			int n_size = (all_train_size[i]*train_size)/100;
			if (i == 0)
				sample25_h = mnist_sample(train_h,n_size);
			else if (i == 1)
				sample50_h = mnist_sample(train_h,n_size);
			else if (i == 2)
				sample75_h = mnist_sample(train_h,n_size);
		}
	}


	double knn_result[4*5*s];
	for (int i=0; i<4*5*s; i++)	//initialize
		knn_result[i] = 0;
	//all for N,k,scheme
	if (!strcmp(argv[2],"all") && !strcmp(argv[4],"all") && !strcmp(argv[5],"all")){	
		
		for (int i=0; i<s; i++){
			for (int j=0; j<5; j++){
				for (int q=0; q<4; q++){
					int n_size = (all_train_size[q]*train_size)/100;
					mnist_dataset_handle sample_h = NULL;
					if (q == 0)
						sample_h = sample25_h;
					else if (q == 1)
						sample_h = sample50_h;
					else if (q == 2)
						sample_h = sample75_h;
					else if (q == 3)
						sample_h = train_h;

					knn_result[i*4*5+j*4+q] = knn(sample_h,test_h,schemes_list[i],all_k[j],n_size);
				}
			}
		}
		printf("# distance k ");
		for (int i=0; i<4; i++)
			printf("%i ", (all_train_size[i]*train_size)/100);
		printf("\n");
		for (int i=0; i<s; i++){
			for (int j=0; j<5; j++){
				printf("%s %i ", schemes_list[i], all_k[j]);
				for (int q=0; q<4; q++)
					printf("%5.2f ", knn_result[i*4*5+j*4+q]);
				printf("\n");
			}
		}
	}
	else if (!strcmp(argv[2],"all")){	//N is all
		if (!strcmp(argv[4],"all")){	//k is all
			for (int j=0; j<5; j++){
				for (int q=0; q<4; q++){
					int n_size = (all_train_size[q]*train_size)/100;
					mnist_dataset_handle sample_h = NULL;
					if (q == 0)
						sample_h = sample25_h;
					else if (q == 1)
						sample_h = sample50_h;
					else if (q == 2)
						sample_h = sample75_h;
					else if (q == 3)
						sample_h = train_h;
					knn_result[j*4+q] = knn(sample_h,test_h,schemename,all_k[j],n_size);
				}
			}
			printf("# distance k ");
			for (int i=0; i<4; i++)
				printf("%i ", (all_train_size[i]*train_size)/100);
			printf("\n");
			for (int j=0; j<5; j++){
				printf("%s %i ", schemename, all_k[j]);
				for (int q=0; q<4; q++)
					printf("%5.2f ", knn_result[j*4+q]);
				printf("\n");
			}
		}
		else if (!strcmp(argv[5],"all")){	//N is all and scheme is all
			k = (int) strtol(argv[4], &end,10);
			for (int i=0; i<s; i++){
				for (int q=0; q<4; q++){
					int n_size = (all_train_size[q]*train_size)/100;
					mnist_dataset_handle sample_h = NULL;
					if (q == 0)
						sample_h = sample25_h;
					else if (q == 1)
						sample_h = sample50_h;
					else if (q == 2)
						sample_h = sample75_h;
					else if (q == 3)
						sample_h = train_h;
					knn_result[i*4*5+q] = knn(sample_h,test_h,schemes_list[i],k,n_size);
				}
			}
			printf("# distance k ");
			for (int i=0; i<4; i++)
				printf("%i ", (all_train_size[i]*train_size)/100);
			printf("\n");
			for (int i=0; i<s; i++){
					printf("%s %i ", schemes_list[i], k);
					for (int q=0; q<4; q++)
						printf("%5.2f ", knn_result[i*4*5+q]);
					printf("\n");
			}
		}
		else{	//k and scheme are not all
			k = (int) strtol(argv[4], &end,10);
			for (int q=0; q<4; q++){
				int n_size = (all_train_size[q]*train_size)/100;
				mnist_dataset_handle sample_h = NULL;
				if (q == 0)
					sample_h = sample25_h;
				else if (q == 1)
					sample_h = sample50_h;
				else if (q == 2)
					sample_h = sample75_h;
				else if (q == 3)
					sample_h = train_h;
				knn_result[q] = knn(sample_h,test_h,schemename,k,n_size);
			}
			printf("# distance k ");
			for (int i=0; i<4; i++)
				printf("%i ", (all_train_size[i]*train_size)/100);
			printf("\n");
			printf("%s %i ", schemename, k);
			for (int q=0; q<4; q++)
				printf("%5.2f ", knn_result[q]);
			printf("\n");
		}
	}
	else if (!strcmp(argv[4],"all")){	//k is all
		N = (int) strtol(argv[2], &end,10);
		if (N > train_size){
			printf("N is larger than the number of images.\n");
			return -1;
		}
		if (!strcmp(argv[5],"all")){	//scheme is all
			for (int i=0; i<s; i++){
				for (int j=0; j<5; j++){
					mnist_dataset_handle sample_h = NULL;
					if (N == 0){
						N = train_size;
						sample_h = train_h;
					}
					else
						sample_h = mnist_sample(train_h,N);
					knn_result[i*4*5+j*4] = knn(sample_h,test_h,schemes_list[i],all_k[j],N);
				}
			}
			for (int i=0; i<s; i++){
				for (int j=0; j<5; j++){
					printf("%s %i ", schemes_list[i], all_k[j]);
					printf("%5.2f ", knn_result[i*4*5+j*4]);
					printf("\n");
				}
			}
		}
		else{	// scheme is not all
			for (int j=0; j<5; j++){
				mnist_dataset_handle sample_h = NULL;
				if (N == 0){
					N = train_size;
					sample_h = train_h;
				}
				else
					sample_h = mnist_sample(train_h,N);
				knn_result[j*4] = knn(sample_h,test_h,schemename,all_k[j],N);
			}
			for (int j=0; j<5; j++){
				printf("%s %i ", schemename, all_k[j]);
				printf("%5.2f ", knn_result[j*4]);
				printf("\n");
			}
		}
	}
	else if (!strcmp(argv[5],"all")){	//scheme is all
		for (int i=0; i<s; i++){
			N = (int) strtol(argv[2], &end,10);
			if (N > train_size){
				printf("N is larger than the number of images.\n");
				return -1;
			}
			k = (int) strtol(argv[4], &end,10);
			mnist_dataset_handle sample_h = NULL;
			if (N == 0){
				N = train_size;
				sample_h = train_h;
			}
			else
				sample_h = mnist_sample(train_h,N);
			knn_result[i] = knn(sample_h,test_h,schemes_list[i],k,N);
		}
		for (int i=0; i<s; i++){
				printf("%s %i ", schemes_list[i], k);
				printf("%5.2f ", knn_result[i]);
				printf("\n");
		}
	}
	else{	// none for all
		N = (int) strtol(argv[2], &end,10);
		if (N > train_size){
			printf("N is larger than the number of images.\n");
			return -1;
		}
		k = (int) strtol(argv[4], &end,10);
		mnist_dataset_handle sample_h = NULL;
		if (N == 0){
			N = train_size;
			sample_h = train_h;
		}
		else
			sample_h = mnist_sample(train_h,N);
		double accuracy = knn(sample_h,test_h,schemename,k,N);
		printf("# distance k %i\n",N);
		printf("%s %i %5.2f\n", schemename, k, accuracy);
	}
	
	return 0;
}


