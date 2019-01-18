# XSoftware WordPress Products

Introduzione
------------

**Products** è un plugin per il Content Management System più famoso e apprezzato, WordPress.

Lo scopo di Products è la gestione dei prodotti su un sito web, fornendo strumenti utili agli amministratori. A tale scopo è utilizzato il database di WordPress per immagazzinare i dati dei prodotti il quale permette la modifica dinamica della struttura della tabella principale `xs_products` esclusi i campi obbligatori `id`, `name`, `lang` e `title`. Le query per manipolare i dati sono gestite dinamicamente tramite l'interfaccia grafica presente nel pannello di amministrazione di WordPress, le azioni permesse sono la cancellazione, l'aggiunta o la modifica di un record, la creazione o la cancellazione dei campi, la visualizzazione di tutti i record. Dopo aver manipolato i dati è possibile creare o utilizzare il predefinito template per la creazione del codice html per i prodotti che verrà visualizzato nelle pagine che contengono lo shortcode `xsoftware_dpc_products` con i suoi rispettivi parametri `lang`, `product`, `field`.

L'intero codice sorgente è scritto in PHP e usa alcune funzioni di WordPress, inoltre il progetto dipende dal framework di XSoftware, quindi sarà necessario abilitare il framework per poter utilizzare correttamente il plugin. I fogli di stile CSS usati sono quelli predefiniti di WordPress per il pannello di amministrazione, oltre a quelli del framework di XSoftware. È possibile aggiungere o modificare il foglio di stile per le pagine generate dallo shortcode. È inoltre presente codice JavaScript per la creazione di messaggi di allerta.