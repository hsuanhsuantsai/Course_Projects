//20170130
//Flora Tsai

//knn algorithm implementation

#include <stdio.h>
#include <stdlib.h>
#include <limits.h>
#include <time.h>
#include "mnist.h"
#include "distance.h"
#include "knn.h"


static inline
int find_max(double *array, int k){
	int max=0;
	int index=0;
	for (int i=0; i<k; i++){
		if (max< array[i]){
			max = array[i];
			index = i;
		}
	}
	return index;
}

double knn(mnist_dataset_handle train_h, mnist_dataset_handle test_h, char *schemename, int k, int N){
	
	if (k == 0){
		printf("Invalid k value\n");
		return -1;
	}
	if (train_h == MNIST_DATASET_INVALID || train_h == NULL){
		printf("MNIST_DATASET_INVALID\n");
		return -1;
	}

	distance_t d = create_distance_function(schemename);
	int x_1=0;
	int x_2=0;
	int y_1=0;
	int y_2=0;
	mnist_image_size(train_h,&x_1,&y_1);
	mnist_image_size(test_h,&x_2,&y_2);
	if (N == 0)
		N = mnist_image_count(train_h);
	int nimage = mnist_image_count(test_h);
	if (x_1 != x_2 && y_1 != y_2)	//image size doesn't fit
		return -1;

	int correct=0;
	//int wrong=0;
	mnist_image_handle img = mnist_image_begin(test_h);
	int count=0;
	printf("K=%i\n", k);
	time_t start = time(0);
	double diff = difftime(time(0),start);
	double cur_time = diff;
	for (int z=0; z<nimage; z++){
		int img_label=-1;
		double freq[10];
		for (int i=0; i<10; i++)	//initialize
			freq[i]=0;
		double distance[k];	//k nearest distance
		int d_label[k];		//k nearest label
		for (int i=0; i<k; i++){		//initialize
			distance[i]=(double) INT_MAX;
			d_label[i]=-1;
		}
		mnist_image_handle temp = mnist_image_begin(train_h);
		for (int i=0; i<N; i++){
			double dist = distance_func(d,img,temp,train_h);
			int index = find_max(distance,k);	//find the max element in distance array
			if (distance[index]>dist){	//replace with current distance
				distance[index]=dist;
				int temp_label = mnist_image_label(temp);
				d_label[index] = temp_label;
			}
			temp = mnist_image_next(temp);
		}
		for (int i=0; i<k; i++)	//count freq
			freq[(d_label[i])]++;

		int freq_i = find_max(freq,10);		//freq index
		int indicator=0;	//indicate all the same freq
		int same=0;	//how many same freq
		for (int i=0; i<k; i++){
			if ((freq[freq_i] == freq[i]) && (i != freq_i))
				same++;
		}
		if (same == 10)
			indicator=1;

		/*assign img_label*/
		if (indicator){	//if all the freqs are the same
			double min=(double) INT_MAX;
			for (int i=0; i<k; i++){
				if (min>distance[i]){
					min=distance[i];
					img_label = d_label[i];
				}
			}
		}
		else
			img_label = freq_i;

		/*check correctness*/
		if (img_label == mnist_image_label(img))
			correct++;
		// else
		// 	wrong++;

		img = mnist_image_next(img);
		count++;

		diff = difftime(time(0),start);
		if (diff>cur_time){
			printf("[%s] %i/%i (%*.*lf%%) ", schemename, count, nimage, 6,2,(double) (count*100)/nimage);
			printf("%i/%i (%*.*lf%%)\n", correct, count, 6,2, (double) (correct*100)/count);
			cur_time++;
		}
	}
	printf("[%s] %i/%i (%*.*lf%%) ", schemename, count, nimage, 6,2,(double) (count*100)/nimage);
	printf("%i/%i (%*.*lf%%)\n", correct, count, 6,2, (double) (correct*100)/count);

	return (double) (correct*100)/count;
}
