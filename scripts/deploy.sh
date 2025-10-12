#!/bin/bash

# CloudCanvas GCP Deployment Script
set -e

echo "🚀 Deploying CloudCanvas to GCP..."

# Set GCP project
PROJECT_ID="your-project-id"
CLUSTER_NAME="cloudcanvas-cluster"
ZONE="us-central1-a"

# Authenticate with GCP
echo "🔐 Authenticating with GCP..."
gcloud auth configure-docker

# Set project
gcloud config set project $PROJECT_ID

# Get cluster credentials
echo "📋 Getting cluster credentials..."
gcloud container clusters get-credentials $CLUSTER_NAME --zone $ZONE

# Build and push images
echo "🐳 Building and pushing Docker images..."
docker build -t gcr.io/$PROJECT_ID/cloudcanvas-php:latest -f docker/php/Dockerfile .
docker build -t gcr.io/$PROJECT_ID/cloudcanvas-nginx:latest -f docker/nginx/Dockerfile .

docker push gcr.io/$PROJECT_ID/cloudcanvas-php:latest
docker push gcr.io/$PROJECT_ID/cloudcanvas-nginx:latest

# Deploy to Kubernetes
echo "⚙️ Deploying to Kubernetes..."
kubectl apply -f kubernetes/configmap.yaml
kubectl apply -f kubernetes/secrets.yaml
kubectl apply -f kubernetes/deployment.yaml
kubectl apply -f kubernetes/service.yaml
kubectl apply -f kubernetes/ingress.yaml
kubectl apply -f kubernetes/hpa.yaml

# Wait for deployment to complete
echo "⏳ Waiting for deployment to complete..."
kubectl rollout status deployment/cloudcanvas-app

# Get external IP
EXTERNAL_IP=$(kubectl get service cloudcanvas-service -o jsonpath='{.status.loadBalancer.ingress[0].ip}')
echo "✅ Deployment complete! Access your application at: http://$EXTERNAL_IP"

# Show pod status
echo "📊 Current pod status:"
kubectl get pods