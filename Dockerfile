FROM php:8.2-apache

# Cài extension PHP cần thiết (PDO + mysqli, code dùng cả 2)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Bật các module Apache cần cho headers, SSL, rewrite
RUN a2enmod rewrite headers ssl

# ==== CÀI MODSECURITY + OWASP CORE RULE SET ====
RUN apt-get update && apt-get install -y \
      libapache2-mod-security2 git openssl \
    && a2enmod security2 \
    && git clone --depth 1 https://github.com/coreruleset/coreruleset /etc/modsecurity/crs \
    && cp /etc/modsecurity/crs/crs-setup.conf.example /etc/modsecurity/crs/crs-setup.conf \
    && cp /etc/modsecurity/modsecurity.conf-recommended /etc/modsecurity/modsecurity.conf \
    && sed -i 's/SecRuleEngine DetectionOnly/SecRuleEngine On/' /etc/modsecurity/modsecurity.conf \
    && echo "IncludeOptional /etc/modsecurity/crs/crs-setup.conf" >> /etc/apache2/mods-enabled/security2.conf \
    && echo "IncludeOptional /etc/modsecurity/crs/rules/*.conf" >> /etc/apache2/mods-enabled/security2.conf \
    && rm -rf /var/lib/apt/lists/*

# Tạo chứng chỉ SSL tự ký (HTTPS bắt buộc theo mục 4 của đề)
RUN mkdir -p /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/apache.key \
    -out /etc/apache2/ssl/apache.crt \
    -subj "/C=VN/ST=DongThap/L=CaoLanh/O=WebBookLab/CN=localhost"

# Copy cấu hình vhost (headers + SSL)
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY apache-ssl.conf /etc/apache2/sites-available/default-ssl.conf
RUN a2ensite default-ssl

EXPOSE 80 443
