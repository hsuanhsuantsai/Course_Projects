//20170130
//Flora Tsai

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <arpa/inet.h>
//#include <assert.h>
#include <time.h>
#include "mnist.h"

int cmp (const void * a, const void * b)
{
   return ( *(int*)a - *(int*)b );
}

struct mnist_dataset_t{
	int lmagic_number;
	int imagic_number;
	int nitem;
	int x;
	int y;
	mnist_image_handle begin;
} mnist_dataset_t;

struct mnist_image_t{
	int *pixel;
	unsigned char label;
	mnist_image_handle next;
} mnist_image_t;

mnist_dataset_handle mnist_open (const char * name){
	char *label=malloc(sizeof(name)+19);
	char *image=malloc(sizeof(name)+19);
	strcpy(label, name);
	strcat(label, "-labels-idx1-ubyte");
	strcpy(image, name);
	strcat(image, "-images-idx3-ubyte");
	mnist_dataset_handle h=malloc(sizeof(mnist_dataset_t));
	FILE *flabel=fopen(label, "rb");
	FILE *fimage=fopen(image, "rb");
	if (flabel==NULL || fimage==NULL)	//check file open succesfully
		return MNIST_DATASET_INVALID;
	int lmagic_temp=0;
	int imagic_temp=0;
	fread(&lmagic_temp, 4, 1, flabel);
	fread(&imagic_temp, 4, 1, fimage);
	h->lmagic_number=ntohl(lmagic_temp);
	h->imagic_number=ntohl(imagic_temp);
	
	if (h->lmagic_number!=2049 || h->imagic_number!=2051){	//check magic number
		fclose(flabel);
		fclose(fimage);
		return MNIST_DATASET_INVALID;
	}

	int nimage=0;
	int nitem_temp=0;
	int x_temp=0;
	int y_temp=0;
	fread(&nitem_temp, 4, 1, flabel);
	fread(&nimage, 4, 1, fimage);
	fread(&y_temp, 4, 1, fimage);
	fread(&x_temp, 4, 1, fimage);
	h->nitem=ntohl(nitem_temp);
	h->y=ntohl(y_temp);
	h->x=ntohl(x_temp);

	mnist_image_handle img=malloc(sizeof(mnist_image_t));
	h->begin=img;
	int x=h->x;
	int y=h->y;
 	img->pixel=malloc(sizeof(int)*x*y);
 	unsigned char buffer[x*y];
	fread(buffer, 1, x*y, fimage);
	fread(&(img->label), 1, 1, flabel);
	for (int i=0; i<x*y; i++)
		img->pixel[i]=(int)buffer[i];

	for (int i=1; i<ntohl(nimage); i++){
		mnist_image_handle temp=malloc(sizeof(mnist_image_t));
		img->next=temp;
		temp->pixel=malloc(sizeof(int)*x*y);
		unsigned char buffer_t[x*y];
		fread(buffer_t, 1, x*y, fimage);
		fread(&(temp->label), 1, 1, flabel);
		for (int i=0; i<x*y; i++)
			temp->pixel[i]=(int) buffer_t[i];
		img=temp;
	}

	fclose(flabel);
	fclose(fimage);
	free(label);
	free(image);

	return h;
}

mnist_dataset_handle mnist_create(unsigned int x, unsigned int y){
	mnist_dataset_handle new=malloc(sizeof(mnist_dataset_t));
	new->x=(int)x;
	new->y=(int)y;
	new->begin=NULL;
	return new;
}

void mnist_free (mnist_dataset_handle handle){
	if (handle->begin == NULL)
		free(handle);
	else{
		mnist_image_handle img;
		img=handle->begin;
		while(img->next!=NULL){
			mnist_image_handle temp;
			temp=img->next;
			free(img->pixel);
			free(img);
			img=temp;
		}
		free(img->pixel);
		free(img);
		free(handle);
	}
}

int mnist_image_count (const mnist_dataset_handle handle){
	if (handle == MNIST_DATASET_INVALID)
		return -1;
	return handle->nitem;
}

void mnist_image_size (const mnist_dataset_handle handle,
                       int * x, int * y){
	if (handle == MNIST_DATASET_INVALID){
		*x=0;
		*y=0;
	}
	else{
		*x=handle->x;
		*y=handle->y;
	}
}



int mnist_image_total_size(const mnist_dataset_handle handle){
	int x=0;
	int y=0;
	mnist_image_size(handle,&x,&y);
	int size=x*y;
	//assert(size!=0);
	return size;
}

mnist_image_handle mnist_image_begin (const mnist_dataset_handle handle){
	if (handle->begin==NULL || handle == MNIST_DATASET_INVALID)
		return MNIST_IMAGE_INVALID;
	return handle->begin;
}

const int * mnist_image_data (const mnist_image_handle h){
	const int * cptr=h->pixel;
	return cptr;
}

int mnist_image_label (const mnist_image_handle h){
	if (h == MNIST_IMAGE_INVALID)
		return -1;
	int label = h->label;
	return label;
}

mnist_image_handle mnist_image_next (const mnist_image_handle h){
	if (h == NULL)
		return MNIST_IMAGE_INVALID;
	return h->next;
}

mnist_image_handle mnist_image_add_after (mnist_dataset_handle h,
      mnist_image_handle i,
      const unsigned char * imagedata, unsigned int x, unsigned int y,
      unsigned int label){
	if ((int)x!=h->x || (int)y!=h->y)
			return MNIST_IMAGE_INVALID;

	mnist_image_handle new=malloc(sizeof(mnist_image_t));
	new->label=label;
	new->pixel=malloc(sizeof(int)*x*y);

	for (int i=0; i<x*y; i++)
		new->pixel[i]=(int)imagedata[i];

	if (i == MNIST_IMAGE_INVALID){
		new->next=h->begin;
		h->begin=new;
	}
	else{
		new->next=i->next;
		i->next=new;
	}

	return new;
}

bool mnist_save(const mnist_dataset_handle h, const char * filename){
	char *label=malloc(sizeof(filename)+19);
	char *image=malloc(sizeof(filename)+19);
	strcpy(label, filename);
	strcat(label, "-labels-idx1-ubyte");
	strcpy(image, filename);
	strcat(image, "-images-idx3-ubyte");
	FILE *flabel=fopen(label, "wb");
	FILE *fimage=fopen(image, "wb");
	if (flabel==NULL || fimage==NULL)
		return false;

	int lmagic_number_temp=htonl(h->lmagic_number);
	int imagic_number_temp=htonl(h->imagic_number);
	int nitem_temp=htonl(h->nitem);
	int x_temp=htonl(h->x);
	int y_temp=htonl(h->y);

	fwrite(&lmagic_number_temp, 4, 1, flabel);
	fwrite(&imagic_number_temp, 4, 1, fimage);
	fwrite(&nitem_temp, 4, 1, flabel);
	fwrite(&nitem_temp, 4, 1, fimage);
	fwrite(&y_temp, 4, 1, fimage);	//rows
	fwrite(&x_temp, 4, 1, fimage);	//columns

	mnist_image_handle img;
	img=h->begin;
	unsigned char buffer[(h->x)*(h->y)];
	for (int i=0; i<(h->x)*(h->y); i++)
		buffer[i]=(unsigned char)img->pixel[i];

	fwrite(buffer, 1, (h->x) * (h->y), fimage);
	fwrite(&(img->label), 1, 1, flabel);

	for (int i=1; i<h->nitem; i++){
		mnist_image_handle temp;
		temp=img->next;
		unsigned char buffer2[(h->x)*(h->y)];
		for (int i=0; i<(h->x)*(h->y); i++)
			buffer2[i]=(unsigned char)temp->pixel[i];

		fwrite(buffer2, 1, (h->x) * (h->y), fimage);
		fwrite(&(temp->label), 1, 1, flabel);
		img=temp;
	}


	fclose(flabel);
	fclose(fimage);
	free(label);
	free(image);

	return true;
}

mnist_dataset_handle mnist_sample(mnist_dataset_handle train, int N){
	int x = train->x;
	int y = train->y;
	int nitem = train->nitem;
	if (N > nitem){
		printf("N is larger than the number of items in the dataset\n");
		return MNIST_DATASET_INVALID;
	}
	mnist_dataset_handle new = malloc(sizeof(mnist_dataset_t));
	new->lmagic_number = train->lmagic_number;
	new->imagic_number = train->imagic_number;
	new->nitem = N;
	new->x = train->x;
	new->y = train->y;
	mnist_image_handle temp = train->begin;
	mnist_image_handle temp_sample = NULL;
	
	/*use reservoir sampling algorithm*/
	srand(time(NULL));
	int R[N];
	for (int i=0; i<N; i++)
		R[i]=i;
	for (int i=N; i<train->nitem; i++){
		int j = (rand()%i);
		if (j <= N-1)
			R[j] = i;
	}
	qsort(R,N,sizeof(int),cmp);
	int r_i=1;	//index of R
	
	for (int i=0; i<train->nitem; i++){
		if (i == R[r_i]){
			temp_sample->next = mnist_copy_image(temp,x,y);
			temp_sample = temp_sample->next;
			r_i++;
		}
		else if (i == R[0]){
			temp_sample = mnist_copy_image(temp,x,y);
			new->begin = temp_sample;
		}
		temp=temp->next;
	}
	
	return new;
}

mnist_image_handle mnist_copy_image(const mnist_image_handle img, int x, int y){
	mnist_image_handle new = malloc(sizeof(mnist_image_t));
	new->pixel = malloc(sizeof(int)*x*y);
	for (int i=0; i<x*y; i++)
		new->pixel[i] = img->pixel[i];
	new->label = img->label;
	new->next = NULL;

	return new;
}

