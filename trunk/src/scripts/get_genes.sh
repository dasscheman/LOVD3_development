#!/bin/bash
rm hgnc_filtered_set.txt
genes=`awk '{print $1}' LOVD_UploadList.txt`
awk '{print $2}' LOVD_UploadList.txt >transcripts.txt
count=0
for gene in $genes
do
	((count ++))
	if [[ count -gt 100 ]]; then
		echo ${count}:${gene}
		exit		
		count=0
	fi
	temp=`grep -A 1 -m 1 -w "${gene}" hgnc_genes.txt`
	echo "${temp}">>hgnc_filtered_set.txt
done
