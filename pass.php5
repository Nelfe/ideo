<script language="javascript">
	function verif(){
		if(document.pass.email.value==0){ 
			alert("Veuillez entrer un email")
		}else if(document.pass.login.value==0){
			alert("Veuillez renseigner un login")
		}
		else document.pass.submit();
	}
</script>

<?php
/* $Revision: 195 $ */
// Paramètres d'inclusion
include 'include.php';

// Classes standard
include 'std_includes.php';


$page = new page();

// Déclaration de la date
$date = new date();

// Déclaration et ouverture de la session
$session = session::getGlobalInstance();

$rep = $session->get(__FILE__,__LINE__,'rep_ressources');

$pref = $session->get(__FILE__,__LINE__,'preferences');

// Connexion à la bdd
$db = new database();

$pass= md5(uniqid(mt_rand()));
$pass = substr($pass,0,6);

if(isset($_POST['email']))
{
	$email = $_POST['email'];

	if($db->update('membres', array('pass'=>'"'.session::cryptPassword($pass).'"'),'email= "'.htmlspecialchars($email).'" AND nom="'.htmlspecialchars($_POST['login']).'"'))
	{
		$entete = 'From: '.MAIL_CONTACT;
		$sujet = '[ideo] Mot de passe';
		$message = 'Votre nouveau mot de passe est : '.$pass;
		mail($email,$sujet,$message,$entete);
		$page->ajouteAlerte("un email vous a été envoyé avec votre nouveau mot de passe");
	}
	else
		$page->ajouteAlerte('Compte inexistant.','error');
}

// Création de la page.
$page->setTemplate('page','commun/formulaireMDP');
$page->setTemplate('menu_principal','index/menu_principal');
/*** Affichage de la page ***/

$page->ajouteStyle('fichierPHP','style.php');

$page->affiche('entete');
$page->affiche('banniere');
$page->affiche('wiki/menus/menu_nonlogue');
$page->affiche('commun/formulaireMDP');
$page->affiche('index/menu_droite');
$page->affiche('pied2page');
?>






