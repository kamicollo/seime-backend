Seime.lt kodas pateikiamas su Creative Commons BY-NC-SA 3.0 licencija:
http://creativecommons.org/licenses/by-nc-sa/3.0/

## SEIME.LT KODO DOKUMENTACIJA  a.k.a BEWARE THERE BE DRAGONS ##

Pilnos Seime.lt dokumentacijos vis dar neprisiruošėme parengti. Tad naršyti po
kodą kol kas teks pusiau užrištomis akimis. Bet kokiu atveju, žemiau pateikiame 
trumpą kodo struktūros santrauką ir kodo pavyzdžių. Sėkmės, o jei iškiltų 
neišsprendžiamų klausimų - visada gali parašyti į info@seime.lt!

### KODO STRUKTŪRA ###

- Pagrindinis kodas laikomas aplanke `classes/`. Tai buvo pirmasis projektas, 
kuriame Seime.lt komanda realiai išbandė OOP, tad jame pilna high-coupling ir
low-cohesion pavyzdžių. Pagrindiniai principai tokie:

	- `Factory` klasė atsakinga už objektų saugojimą / sukūrimą iš DB ir keliavimą objektų medžiu (sibling / parent / etc metodai).	
	- Kiekvienas Seimo darbo objektas (sesija, posėdis ir t.t.) turi savo klasę.
	- Bendri Seimo darbo objektų metodai, veikimo struktūros griaučiai apibrėžti klasėje `HTMLObject (abstractions.php)`
	- `utilities.php` faile saugomos pagalbinės klasės ir funkcijos.

- Aplanke `extensions/` laikomos klasės, kurios prideda papildomo funkcionalumo
prie Seimo darbo klasių. T.y., `classes/` aplanke esančios klasės naudoja tik
"oficialius" Seimo svetainėje pateikiamus duomenis. `Extensions` aplanke esančios
klasės prideda papildomus skaičiavimus (kaip, pvz., sub-klausimų lygio dalyvavimo
statistiką). Tai, kurios klasės naudojamos, nustatoma perduodant Factory klasei
klasių pavadinimus, kaip antrą parametrą.

- Aplanke `cache/` saugomi visi parsiųsti http://lrs.lt HTML dokumentai. Saugojimo 
mechanizmas įgyvendintas Utilities klasėje, `classes/abstractions.php` dokumente.

- Aplanke `sqls/` saugomos SQL užklausos, kurių pagalba sugeneruojamos kai kurios 
SQL lentelės (papildomi duomenys). Jas naudoja `classes/Updater.php` klasė.

### DARBAS SU KODU ###
	
Praktiškai, norint susirinkti duomenis reikia susikurti sesijos objektą
ir jį (bei sub-objektus) inicijuoti:
```php
<?php	
	$s = $Factory->getObject('session', SESIJOS_URL); 
	//SESIJOS_URL pavyzdys: http://www3.lrs.lt/pls/inter/w5_sale.ses_pos?p_ses_id=91
	$s->scrapeData(true); // TRUE = iš naujo parsisiųsti HTML failą, net jei yra cache versija
	$this->session->initialise(); //Inicijuojamas sesijos objektas (užpildomi laukai pagal HTML informaciją)
	$this->session->initialiseChildren(true); //Rekursiškai inicijuojami visi sub-objektai.
	$s->saveData();
?>
```
Tiesa, taip nebus užpildytos visos SQL lentelės, trūks kai kurios kitos informacijos.
	
Pilnas informacijos surinkimo / atnaujinimo pavyzdys pateikiamas `update.php`
Jis naudoja `Updater` klasę, esančią `classes/Updater.php`, kuri sukurta būtent duomenų
surinkimui ar jų atnaujinimui.
