Am creat urmatoarele componente in azure : 
	
	Form recognizer

	SQL server

	SQL database

	Storage account

	App Service

Am rulat si doua comenzi prin composer pentru a lua sdk-urile necesare dar sincer nu imi mai aduc aminte care exact . 

Le-am configurat pentru cerintele probelemei ( pentru php am creat blob storage-ul in store account , am facut in sql database o baza de date noua si am adaugat fieldurile de care aveam nevoie pentru stocare)
Am luat toate datele din fiecare pentru a ma conecta la aceste componente
Am facut codul php atasat site-ului , conectand baza de date si am facut practic doua comenzi de curl una de post si una de get , din cea de post a trebuit sa iau valoarea de la fieldul Operation-location si sa o adaug in cea de get deoarece altfel nu imi primeam continutul . 

Dupa am luat doar datele relevante din jsonul primit si le-am afisat . 