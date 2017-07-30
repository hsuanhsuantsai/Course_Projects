//20170130
//Flora Tsai

#include "mnist.h"
#include "distance.h"
#include <stdio.h>
#include <stdlib.h>



static
int get_index(int i, int j, int col){
	return i*col+j;
}

static
int * get_cropped(const mnist_image_handle ih, const mnist_dataset_handle handle, 
					int *new_size){
	
	int x=0;
	int y=0;
	mnist_image_size(handle,&x,&y);
	if (x<8 && y<8){
		*new_size=-1;
		return NULL;
	}
	
	*new_size=(x-8)*(y-8);

	if (!(*new_size))
		return NULL;

	const int *A=mnist_image_data(ih);
	int *a=malloc(sizeof(int)*(*new_size));
	int ai=0;	//a index
	for (int i=4; i<y-4; i++){
		for (int j=4; j<x-4; j++)
			a[ai++]=A[get_index(i,j,x)];
	}
	return a;

}

static
double crop_distance_func(distance_t dthis, const mnist_image_handle a, 
	const mnist_image_handle b, const mnist_dataset_handle handle){
	
	int size=0;
	int *A;
	int *B;
	A=get_cropped(a,handle,&size);
	if (A == NULL)
		return -1;

	if (!size)	//size=0
		return 0;

	B=get_cropped(b,handle,&size);
	if (B == NULL)
		return -1;

	double sum=0;
	for (int i=0; i<size; i++)
		sum+=(A[i]-B[i])*(A[i]-B[i]);

	free(A);
	free(B);
	return sum;
}

const char * crop_distance_describe(distance_t dthis){
	return "crop";
}

distance_t crop_func_create(){
	distance_t d=malloc(sizeof(DISTANCE_T));
	d->func = crop_distance_func;
	d->describe = crop_distance_describe;

	return d;
}
