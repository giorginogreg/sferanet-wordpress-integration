# Variabili d'ambiente
In produzione sono stati inseriti nel VHost SSL delle variabili d'ambiente per simulare quanto fatto con il docker-compose
nel file /etc/apache2/sites-available/000-default-le-ssl.conf

# Debug
E' possibile abilitare la variabile di debug che scriverà un log sotto la cartella wp-content/uploads/SferanetApiLogs  
La variabile d'ambiente è la seguente **SFERANET_DEBUG**, da definire nel wp-config o nel docker-compose.

