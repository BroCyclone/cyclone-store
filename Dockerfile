FROM php:8.2-cli

# Zip extension နှင့် လိုအပ်သည့် library များ install ပြုလုပ်ခြင်း
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip

WORKDIR /var/www/html
COPY . .
RUN mkdir -p data uploads && chmod -R 777 data uploads
EXPOSE 10000
CMD ["php", "-S", "0.0.0.0:10000", "router.php"]
