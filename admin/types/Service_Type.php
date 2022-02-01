<?php //phpcs:ignore
//phpcs:ignore
class Service_Type {
	const PACKAGE    = 'PKG'; // Pacchetti Da Catalogo
	const STRUCTURE  = 'STR'; // Hotel e strutture ricettive in genere
	const EXCURSION  = 'ESC'; // Escursioni
	const ENSURANCE  = 'ASS'; // Assicurazione
	const VISA       = 'VIS'; // Visto
	const RENT       = 'NOL'; // Noleggio auto e altri mezzi
	const RESTAURANT = 'RIST'; // Ristoranti e strutture di ristorazione in genere
	const EVENT      = 'EVE'; // Evento/Spettacolo
	const GENERIC    = 'GEN'; // Categoria residuale per servizi generali che non rientrano nelle precedenti categorie
	const UNIT_COST  = 'COSTI'; // Costi unitari (es. Autobus, guide) il cui valore non è legato alla singola vendita
}
