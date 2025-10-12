#!/bin/bash

# GCP Infrastructure Setup Script
set -e

PROJECT_ID="your-project-id"
CLUSTER_NAME="cloudcanvas-cluster"
ZONE="us-central1-a"

echo "🏗️ Setting up GCP infrastructure for CloudCanvas..."

# Enable required APIs
echo "🔧 Enabling GCP APIs..."
gcloud services enable \
    container.googleapis.com \
    containerregistry.googleapis.com \
    compute.googleapis.com \
    storage-component.googleapis.com \
    mongodb.googleapis.com

# Create GKE cluster
echo "🎯 Creating GKE cluster..."
gcloud container clusters create $CLUSTER_NAME \
    --zone $ZONE \
    --num-nodes 2 \
    --machine-type e2-medium \
    --enable-ip-alias \
    --enable-autoscaling \
    --min-nodes 1 \
    --max-nodes 5

# Create GCS bucket for images
echo "🪣 Creating GCS bucket..."
gsutil mb -l us-central1 gs://$PROJECT_ID-cloudcanvas/

# Make bucket publicly readable for images
gsutil iam ch allUsers:objectViewer gs://$PROJECT_ID-cloudcanvas

echo "✅ GCP infrastructure setup complete!"
echo "📝 Next steps:"
echo "1. Run ./scripts/deploy.sh to deploy the application"
echo "2. Set up Cloud MongoDB Atlas and update configmap.yaml"
echo "3. Configure domain and SSL certificate if needed"