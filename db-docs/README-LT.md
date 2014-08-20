# SEIME.LT DUOMENŲ DOKUMENTACIJA #

Šiame dokumente trumpai apibūdinsime Seime.lt SQL duomenų schemą. Tai nebus pilna
ir tiksli dokumentacija, bet jos turėtų užtekti suprasti pagrindinius duomenų 
aspektus - visą kitą turėtų būti galima (gana) nesunkiai suprasti ir be tikslių
aprašymų. Bet kuriuo atveju - visada galima parašyti į info@seime.lt ir paprašyti
patikslinti vieną ar kitą detalę. Prieš pradedant skaityti šią dokumentaciją
rekomenduojame susipažinti su duomenų schemos brėžiniu, kuris pateikiamas seime.lt.pdf.

Šios dokumentacijos struktūra tokia:

* aptariamas pagrindinis objektų (sesijų, posėdžių, klausimų, veiksmų) medis
* aptariama įvairi oficiali informacija apie objektus
* aptariami papildomi Seime.lt komandos apskaičiuoti duomenys

Seime.lt duomenys pateikiami su Creative Commons BY-NC-SA licencija:
http://creativecommons.org/licenses/by-nc-sa/3.0/

## PAGRINDINIS OBJEKTŲ MEDIS ##

Seimo darbas vyksta sesijomis - kiekvienais metais vyksta pavasario ir rudens
sesijos, taip pat pagal poreikį organizuojamos neeilinės sesijos. Duomenys apie
Seimo sesijas saugomi lentelėje `SESSIONS`.

Kiekvienos sesijos metu antradieniais ir ketvirtadieniais vyksta eiliniai Seimo
posėdžiai. Taip pat pagal poreikį organizuojami neeiliniai posėdžiai. Duomenys
apie Seimo posėdžius saugomi lentelėje `SITTINGS`. Kiekvienas posėdis priklauso 
vienai iš sesijų ir yra susietas su ja ryšiu `SITTINGS.SESSIONS_ID = SESSIONS.ID`.

Kiekvieno posėdžio metu yra nagrinėjami darbotvarkėje numatyti klausimai. Duomenys
apie kiekvieną klausimą saugomi lentelėje `QUESTIONS`. Kiekvienas klausimas priklauso
vienam iš posėdžių ir yra susietas su juo ryšiu `QUESTIONS.SITTINGS_ID = SITTINGS.ID`.

Kiekvieno klausimo metu yra vykdomi "veiksmai" - Seimo narys pasisako, vyksta
registracija į balsavimą, vyksta balsavimas ir t.t. Veiksmų tipai aptariami žemiau.
Duomenys apie kiekvieną veiksmą saugomi lentelėje `ACTIONS`. Kiekvienas veiksmas
priklauso vienam iš klausimų ir yra susietas ryšiu `ACTIONS.QUESTIONS_ID = QUESTIONS.ID`.

## PAPILDOMA INFORMACIJA APIE POSĖDŽIUS ##

Seimo Statuto numatyta tvarka yra nustatoma, ar Seimo narys dalyvavo posėdyje:

> 11 straipsnis. Laikoma, kad Seimo narys dalyvavo Seimo posėdyje, jeigu jis 
> užsiregistravo daugiau kaip pusėje iš anksto numatytų ir numatytu laiku įvykusių
> balsavimų dėl teisės akto priėmimo ir užsiregistravo visuose tos dienos Seimo
> posėdžiuose. Laikoma, kad Seimo narys dalyvavo Seimo komiteto ar komisijos posėdyje,
> jeigu jis užsiregistravo posėdžio protokolo priede pasirašytinai.
	
Ši oficiali dalyvavimo statistika saugoma lentelėje `SITTING_PARTICIPATION` formatu
`(MEMBERS_ID, SITTINGS_ID, PRESENCE)`, kur pirmieji du laukai yra nuorodos į `SITTINGS`
ir `MEMBERS` lenteles, o `PRESENCE` turi reikšmę 0 (nedalyvavo) arba 1 (dalyvavo).

## PAPILDOMA INFORMACIJA APIE KLAUSIMUS ##
	
Kai kurie svarstomi klausimai turi su susijusius dokumentus (pvz., svarstomo įstatymo
tekstas). Ši informacija saugoma lentelėje `ITEMS`, kuri susieta ryšiu 
`ITEMS.QUESTIONS_ID = QUESTIONS.ID`. Be to, kai kuriais atvejais 	šie dokumentai yra
pristatomi pranešėjų. Ši informacija saugoma lentelėje `PRESENTERS`, kuri susieta ryšiu
`PRESENTERS.ITEMS_ID` = `ITEMS.ID. PRESENTERS` lentelėje pranešėjų vardai saugomi tekstiniu
formatu ir nėra susieti su `MEMBERS` lentele (nes pranešėjai ne visada yra Seimo nariai).
Tačiau ryšys `PRESENTERS.PRESENTER` = `MEMBERS.NAME` dažniausiai veikia be klaidų.

## PAPILDOMA INFORMACIJA APIE VEIKSMUS ##

Išskiriami 5 veiksmų tipai: 
	
- Seimo narių pasisakymai (`ACTIONS.TYPE` = "speech"). Informacija apie tai, kuris
Seimo narys pasisakė saugoma lentelėje `SPEAKERS` su ryšiais `SPEAKERS.ACTIONS_ID =
ACTIONS.ID` ir `SPEAKERS.MEMBERS_ID = MEMBERS.ID`.

- Registracijos į balsavimus (`ACTIONS.TYPE` = "registration"). Duomenys apie 
registracijas	saugomi lentelėje `REGISTRATIONS` formatu `(MEMBERS_ID, ACTIONS_ID, presence)`,
kur pirmieji du laukai yra nuorodos į `ACTIONS` ir `MEMBERS` lenteles, o `PRESENCE` turi
reikšmę	0 (neužsiregistravo) arba 1 (užsiregistravo).
- Balsavimai (`ACTIONS.TYPE` = "voting"). Duomenys apie balsavimus saugomi lentelėje
`VOTES` formatu `(MEMBERS_ID, ACTIONS_ID, FRACTION, VOTE)`, kur pirmieji du laukai yra
nuorodos į `ACTIONS` ir `MEMBERS` lenteles, `FRACTION` yra tekstinis laukas su Seimo nario
frakcijos santrumpa ir `VOTE`, kuris įgauna reikšmes "abstain" (susilaikė), "accept" 
(balsavo už), "dissappeare" (užsiregistravo, bet nebalsavo), "not presen" 
(neužsiregistravo ir nebalsavo) ir "reject" (balsavo prieš). Visos reikšmės, išskyrus
"dissappeare" yra oficialios. "Disappeare" reikšmės apskaičiavimas aprašome žemiau.	
- Vienbalsiški balsavimai (`ACTIONS.TYPE` = "u_voting"). Papildomos infomacijos nėra.
- Kiti veiksmai (`ACTIONS.TYPE` = "other"). Visi kiti veiksmai. Tarp šių veiksmų yra
ir alternatyvieji balsavimai (ne už/prieš , bet už A/už B tipo balsavimai), kurių
balsavimo duomenys saugomi taip pat, kaip ir paprastų balsavimų.

## SEIME.LT KOMANDOS APSKAIČIUOTI DUOMENYS ##

Vienas pagrindinių Seime.lt projekto tikslų buvo apskaičiuoti tikslesnę nei oficiali
Seimo narių lankomumo statistiką. Tai buvo nuspręsta padaryti suskaidant posėdžių laiką
į mažas dalis tarp registracijų ir skaičiuoti buvimo laiką remiantis šiais intervalais.

- Visų pirma, buvo identifikuoti laiko intervalai tarp registracijų kiekviename klausime,
kurie saugomi lentelėje `SUBQUESTIONS`, kuri susieta su lentele `QUESTIONS` ryšiu 
`SUBQUESTIONS.QUESTIONS_ID = QUESTIONS.ID`.
- Tada buvo rasta, ar Seimo narys dalyvavo posėdyje konkrečiame laiko intervale. 
Buvo laikoma, kad Seimo narys dalyvavo posėdžio dalyje tarp dviejų registracijų, jei
užsiregistravo bent vienoje jų. Šie duomenys saugomi lentelėje `SUBQUESTIONS_PARTICIPATION`
formatu `(MEMBERS_ID, SUBQUESTIONS_ID, PRESENCE)`, kur pirmieji du laukai yra nuorodos
į `SUBQUESTIONS` ir `MEMBERS` lenteles, o `PRESENCE` turi	reikšmę	0 (dalyvavo) arba 1 (nedalyvavo).
- Visi šie duomenys suagreguojami posėdžių lygmenyje ir saugomi lentelėje `PARTICIPATION_DATA`
formatu `(MEMBERS_ID, SITTINGS_ID, OFFICIAL_PRESENCE, HOURS_AVAILABLE, HOURS_PRESENT)`, kur:
    - `MEMBERS_ID` yra nuoroda į `MEMBERS` lentelę;
    - `SITTINGS_ID` yra nuoroda į `SITTINGS` lentelę;
    - `OFFICIAL_PRESENCE` yra oficiali informacija apie tai, ar Seimo narys dalyvavo
posėdyje (žr. PAPILDOMA INFORMACIJA APIE POSĖDŽIUS aukščiau);
    - `HOURS_AVAILABLE` yra visa posėdžio trukmė valandomis;
    - `HOURS_PRESENT` yra Seimo nario buvimo posėdyje laikas, apskaičiuotas pagal
`SUBQUESTIONS_PARTICIPATION` lentelės duomenis.
	
Seime.lt komanda taip pat norėjo identifikuoti tuos atvejus, kai Seimo nariai 
užsiregistruoja į balsavimą, tačiau jame nesudalyvauja (nors jie beveik visada vyksta iš 
karto vienas po kito). Tai buvo padaryta identifikuojant pirmą registraciją prieš kiekvieną
balsavimą. Šie registracijų ir balsavimų ryšiai saugomi lentelėje `VOTING_REGISTRATION`, kur
`VOTE_ID` yra nuoroda į `VOTES.ID` lauką, o `REGISTRATION_ID` yra nuoroda į `REGISTRATIONS.ID` lauką.
Taip pat remiantis šiais duomenimis buvo papildyta lentelę `VOTES`, kur laukui `VOTE` nustatyta
reikšmė "dissapeare" tais atvejais, kai Seimo narys užsiregistravo, bet nedalyvavo balsavime.
