FROM nginx:1.23-alpine

# Copy custom nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf

# Copy application code
COPY src/ /var/www/

# Set permissions
RUN chown -R nginx:nginx /var/www

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]