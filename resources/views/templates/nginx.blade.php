server {
    listen {{ $port }};
    server_name {{ $server_name }};
    root {{ $root }};
    index index.php index.html;

    client_max_body_size {{ $client_max_body_size ?? '25M' }};

    #access_log /var/log/nginx/access.log;
    #error_log /var/log/nginx/error.log;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
} 