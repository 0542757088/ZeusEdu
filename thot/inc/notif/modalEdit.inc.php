<?php

require_once '../../../config.inc.php';

require_once '../../../inc/classes/classApplication.inc.php';
$Application = new Application();

require_once INSTALL_DIR.'/inc/classes/classUser.inc.php';
session_start();

if (!(isset($_SESSION[APPLICATION]))) {
    die("<div class='alert alert-danger'>Votre session a expiré. Veuillez vous reconnecter.</div>");
}

$id = isset($_POST['id'])?$_POST['id']:Null;

$User = $_SESSION[APPLICATION];
$acronyme = $User->getAcronyme();

require_once (INSTALL_DIR."/inc/classes/classThot.inc.php");
$thot = new Thot();

require_once(INSTALL_DIR.'/smarty/Smarty.class.php');
$smarty = new Smarty();

$notification = $thot-> getNotification($id, $acronyme);
$smarty->assign('notification',$notification);

echo json_encode($notification);
