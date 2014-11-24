Henriks filmdatabase
====================

Dette er et program som gjør det mulig å indeksere filmene man har i en mappe
liggende på PC-en og gå gjennom filmoversikten via nettleseren.

Denne versjonen er nylig skrevet om fra en tidligere versjon. Indekserings-
funksjonaliteten og annen funksjonalitet er deaktivert til det blir ferdig
skrevet om og testet.


Oppsett/installasjon
--------------------

* Legg filene på en egen vhost (merk at scriptet må ha tilgang til mappene
med filmene, så må vanligvis kjøre på samme maskin som filmene ligger)
* Opprett base/config.php filen basert på base/config.default.php
* Rediger base/config.php til å passe ditt oppsett og behov
* Åpne nettsiden på adressen du har opprettet for siden

Alternativt kan man også kjøre siden i enkelttilfeller ved hjelp av PHPs innebygde server:

F.eks.: `$ php -S localhost:8000`


Avhengigheter
-------------

For å hente ut informasjon fra filmfilene må man ha ffmpeg installert.


TODO
----

* Gjøre det mulig å indeksere mapper igjen
* Lenker til manage-siden og tilbake
* Optimalisere visningen av filmer


Lisens
------

Se filen LICENSE.