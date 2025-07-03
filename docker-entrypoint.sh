#!/bin/bash
set -e

# Configurar variáveis de ambiente para PHP
echo "<?php" > /var/www/html/env-config.php
echo "// Auto-generated environment configuration" >> /var/www/html/env-config.php
echo "// Timestamp: $(date)" >> /var/www/html/env-config.php
echo "" >> /var/www/html/env-config.php

# Adicionar variáveis de ambiente do Appwrite
echo "// Appwrite Configuration" >> /var/www/html/env-config.php
[ ! -z "$APPWRITE_ENDPOINT" ] && echo "putenv('APPWRITE_ENDPOINT=$APPWRITE_ENDPOINT');" >> /var/www/html/env-config.php
[ ! -z "$APPWRITE_PROJECT_ID" ] && echo "putenv('APPWRITE_PROJECT_ID=$APPWRITE_PROJECT_ID');" >> /var/www/html/env-config.php
[ ! -z "$APPWRITE_DATABASE_ID" ] && echo "putenv('APPWRITE_DATABASE_ID=$APPWRITE_DATABASE_ID');" >> /var/www/html/env-config.php
[ ! -z "$APPWRITE_VIDEO_COLLECTION_ID" ] && echo "putenv('APPWRITE_VIDEO_COLLECTION_ID=$APPWRITE_VIDEO_COLLECTION_ID');" >> /var/www/html/env-config.php
[ ! -z "$APPWRITE_THUMBNAIL_BUCKET_ID" ] && echo "putenv('APPWRITE_THUMBNAIL_BUCKET_ID=$APPWRITE_THUMBNAIL_BUCKET_ID');" >> /var/www/html/env-config.php
[ ! -z "$APPWRITE_VIDEO_BUCKET_ID" ] && echo "putenv('APPWRITE_VIDEO_BUCKET_ID=$APPWRITE_VIDEO_BUCKET_ID');" >> /var/www/html/env-config.php
[ ! -z "$APPWRITE_API_KEY" ] && echo "putenv('APPWRITE_API_KEY=$APPWRITE_API_KEY');" >> /var/www/html/env-config.php

# Definir permissões corretas
chown www-data:www-data /var/www/html/env-config.php
chmod 640 /var/www/html/env-config.php

# Configurar o auto_prepend_file no PHP para carregar automaticamente o arquivo de configuração
echo "auto_prepend_file = /var/www/html/env-config.php" > /usr/local/etc/php/conf.d/appwrite-env.ini

# Executar comando padrão do contêiner Apache
exec apache2-foreground 