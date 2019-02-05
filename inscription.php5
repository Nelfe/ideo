<?php
/* $Revision: 132 $ */

$msg_inscription = 'Le serveur est actuellement trop encombré pour supporter plus de joueurs.
	Les inscriptions sont donc fermées pour le moment.<br />Un nouveau serveur est en cours de commande. Nous prévoyons donc une réouverture des inscriptions dès sa mise en place, courant mars. Merci de votre patience et de votre compréhension.<br /><br />L\'équipe IDEO';
$msg_inscription = false;

// Paramètres d'inclusion
include 'include.php';

// Classes standard
include 'std_includes.php';

// Classes standard
include 'jeu_includes.php';

$session = session::getGlobalInstance('INSCR_IDEO');

$page = page::getGlobalInstance();
$db = database::getGlobalInstance();
$date = new date();
$carte = carte::getGlobalInstance();


$data = $db->select('COUNT(*) AS nb FROM membres WHERE (grade_site >= 0 OR grade_site IS NULL) AND id<>0 AND mort_absolue = 0 AND dernier_acces > '.(time() - 3600 * 24 * 7), RES_UNIQUE);
if($data->nb > LIMITE_JOUEURS)
{
	$msg_inscription = 'Désolé, mais nous n\'autorisons dorénavant pas plus de '.LIMITE_JOUEURS.' joueurs actifs. Nous sommes actuellement en train de réfléchir à une augmentation des ressources du serveur pour accueillir plus de monde. Merci de votre compréhension.<br /><br />L\'équipe d\'IDEO';
}

$data = $db->select('* from options_serveur');
foreach($data as $entry)
{
	$options[$entry->nom] = $entry->valeur;
}

// Récupération des infos d'inscription pour modification
if(isset($_GET['id']))
{
	$data = $db->select('* FROM inscriptions WHERE id="'.$_GET['id'].'"',RES_UNIQUE);
	if($data)
	{
		$var = true;
		$session->set('questionnaire',$var);
		foreach($data as $nom => $val)
		{
		    if($nom == 'dieu' && !$val)
		        $val = 'vide';
		    if($nom != 'date')
			    $session->set($nom,$val);
		}
		$_GET['p'] = 2;
		$session->set('id',$data->id);
		$var = 0;
		$session->set('pts_perso',$var);
	}
	else
	{
	    $page->ajouteAlerte('Cette demande d\'inscription n\'existe pas ou plus. Votre demande a'
			.' sûrement déjà été validée, auquel cas il vous suffit de vous connecter au jeu.'.NL
			.'Il est également possible que votre inscription ait été refusée, auquel cas vous devriez avoir reçu un mail d\'explication.','error');
	}
}
if( $session->verifie('id') && isset($_POST['dc_raison']) )
{
	$dcRaison = request('dc_raison', 'post', 'string');
	
	$session->set('dc_raison', $dcRaison);
	$db->update(
		'inscriptions'
		, array('dc_raison' => data::prepareString($dcRaison), 'retour' => 0)
		, 'id = '.data::prepareString($session->get(__FILE__, __LINE__, 'id'))
	);
	$page->ajouteAlerte('Votre message a bien été enregistré.');
}
/*
if(DEBUG)
{
	$var = true;
	$session->set('questionnaire',$var);
}   */
if(!isset($_GET['p']) || ($_GET['p'] != 'der' && (!is_numeric($_GET['p']) || $_GET['p'] < 1 || $_GET['p'] > 6)))
	$p = false;
else
	$p = $_GET['p'];

if(isset($_POST['act']) && $_POST['act'] == 'questionnaire')
	include 'questionnaire.php';

if($p && !$session->verifie('questionnaire'))
	$p = 'questionnaire';
if($p == 'der')
{
	$p = 6;
	$der = true;
}
else
	$der = false;

if($msg_inscription)
	$p = false;

$data = array(
	array('login','pass','email','race','royaume'),
	array('description','avatar','sexe','taille','poids','yeux','cheveux','lieu',
	'jour','mois','cycle','aime','deteste','apparence','autres'),
	array(/*'pts_perso','force','dex','con','exp','per','intel',*/'competence'),
	array('dieu','protection','negation','vitalite','destruction','alteration','voie'),
	array('temple')
);
$except = array('avatar','protection','negation','vitalite','destruction','alteration');
$numeric = array('royaume','jour','mois','cycle',
	'pts_perso','force','dex','con','exp','per','intel','temple');


$dat = $db->select('royaume, COUNT(*) AS nb FROM membres WHERE mort_absolue = 0 GROUP BY royaume');
$interdit = array();
$total = 0;
foreach($dat as $d)
	$total += $d->nb;

foreach($dat as $d)
{
	if(!DEBUG && ($d->nb * 100 / $total) > 30)
	    $interdit[] = $d->royaume;
}
if(isset($options['inscr_Ithoria']) && $options['inscr_Ithoria'] == 0)
	$interdit[] = 2;
if(isset($options['inscr_Ithoria']) && $options['inscr_Kohr'] == 0)
	$interdit[] = 1;
if(isset($options['inscr_Ithoria']) && $options['inscr_CA'] == 0)
	$interdit[] = 3;
if(isset($options['inscr_Ithoria']) && $options['inscr_PE'] == 0)
	$interdit[] = 4;
if(isset($options['inscr_Ithoria']) && $options['inscr_HB'] == 0)
	$interdit[] = 5;

if(isset($_POST['npage']) && is_numeric($_POST['npage']))
{
	if($_POST['npage'] == 3)
	{
		$session->detruire('protection');
		$session->detruire('negation');
		$session->detruire('vitalite');
		$session->detruire('destruction');
		$session->detruire('alteration');
		$session->detruire('justice');
	}
	foreach($data[$_POST['npage']] as $var)
	{
		if(isset($_POST[$var]))
		{
			$session->set($var, request($var, 'post'));
		}
	}
}

foreach($data as $i => $etape)
{
	if(!$session->verifie(reset($etape)) && $p > $i + 1)
	{
		if(!$der)
			$page->ajouteAlerte('Vous devez faire les étapes dans l\'ordre. Une fois une étape passée, vous pourrez revenir dessus.','warning');
		$p = $i + 1;
		break;
	}
}

if($session->verifie('race'))
{
	$race = $session->get(__FILE__,__LINE__,'race');
}
else
	$race = false;
switch($race)
{
case 'humain':
	$liste_comp = array('forgeron','desert','marais','montagne','eclaireur','camouflage','esquive','musique','marchandage','vol');
	break;
case 'elfe':
	$liste_comp = array('sylvestre');
	break;
case 'orc':
	$liste_comp = array('survie');
	break;
default:
	$liste_comp = array();
}

if($p == 6)
{
	include 'inscription.php';
	if($p == 6 && $session->verifie('id'))
	    $p = 7;
}
if($p == 3)
	$page->ajouteOnload('actualise()');
elseif($p == 4)
	$page->ajouteOnload('actualiseDieu()');
elseif($p == 5)
{

	if($session->verifie('dieu'))
		$d = $session->get(__FILE__,__LINE__,'dieu');
	else
		$d = 0;
	$royaume = $session->get(__FILE__,__LINE__,'royaume');
	if(!is_numeric($d) || $d == 'vide')
	    $d = 0;
	if($d != 0)
		$query = 'type='.PORTAIL_RES.' OR (param='.$d.' AND type='.TEMPLE.')';
	else
		$query = 'type='.PORTAIL_RES;
	$temples = charge_batiment::charge($query,'liste');
	foreach($temples as $i => $t)
	{
		if(isset($t->ville->royaume->id) && $t->ville->royaume->id != $royaume)
			unset($temples[$i]);
	}
	if($session->verifie('temple'))
	    $page->ajouteOnload('infoTemple('.$session->get(__FILE__,__LINE__,'temple').')');
}
$page->ajouteAjaxModule('minimap');
$page->InitAjax();


$rep = REP_RESSOURCES;
$style = 'ideo';
$page->ajouteStyle('fichierPHP','style.php');
$page->ajouteStyle('fichier','inscription.css');
$page->ajouteScript('fichier','inscription.js');
$page->ajouteScript('fichierPHP','inscription.php');
//$page->setSkin(REP_RESSOURCES.'skins/ideo/');

$page->setTemplate('menu_principal','index/menu_principal');

// Si la session doit être fermée cela doit se faire juste avant l'affichage de la page
if($p == 6 || $p == 7)
	$session->fermer();

// AFFICHAGE DE LA PAGE

$page->affiche('entete');
$page->affiche('banniere');

$page->affiche('inscription/menu');

//$page->affiche('index/interface');

if($p == 'questionnaire')
	$page->setTemplate('page','inscription/questionnaire_new');
elseif($p == 'reponses')
	$page->setTemplate('page','inscription/reponses_new');
elseif($p)
	$page->setTemplate('page','inscription/partie'.$p);
elseif($msg_inscription)
	echo '<h2 align="center">'.$msg_inscription.'</h2>';
else
	$page->setTemplate('page','inscription/presentation');

if(!$msg_inscription)
{
	$page->affiche('page');
}

$page->affiche('inscription/menu_droite');

// $page->affiche('commun/logo');
$page->affiche('pied2page');
