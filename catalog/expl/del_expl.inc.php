<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: del_expl.inc.php,v 1.18 2015-04-03 11:16:18 jpermanne Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

require_once("$class_path/expl.class.php");
print "<div class=\"row\"><h1>${msg[313]}</h1></div>";

//R�cup�ration de l'ID de l'exemplaire
if (!$expl_id || !$cb) {
	$requete = "select expl_id, expl_cb from exemplaires where expl_cb='$cb' or expl_id='$expl_id'";
	$result=@pmb_mysql_query($requete);
	if (pmb_mysql_num_rows($result)) {
		$expl_id=pmb_mysql_result($result,0,0);
		$cb=pmb_mysql_result($result,0,1);
	}
}

$requete = "select 1 from pret where pret_idexpl='$expl_id' ";
$result=@pmb_mysql_query($requete);
if (pmb_mysql_num_rows($result)) {
	// gestion erreur pr�t en cours
	error_message($msg[416], $msg[impossible_expl_del_pret], 1, "./catalog.php?categ=isbd&id=$id");
} else {
	//archivage
	if ($pmb_archive_warehouse) {
			exemplaire::save_to_agnostic_warehouse(array(0=>$expl_id),$pmb_archive_warehouse);
	}
	// nettoyage doc. � ranger
	$requete_suppr = "delete from resa_ranger where resa_cb in (select expl_cb from exemplaires where expl_id='".$expl_id."') ";
	$result_suppr = pmb_mysql_query($requete_suppr, $dbh);
	
	$requete = "DELETE FROM exemplaires WHERE expl_cb='$cb' or expl_id='$expl_id'";
	$result = @pmb_mysql_query($requete, $dbh);
	audit::delete_audit (AUDIT_EXPL, $expl_id) ;
	
	$query_caddie = "select caddie_id from caddie_content, caddie where type='EXPL' and object_id ='$expl_id' and caddie_id=idcaddie ";
	$result_caddie = @pmb_mysql_query($query_caddie, $dbh);
	while($cad = pmb_mysql_fetch_object($result_caddie)) {
		$req_suppr_caddie="delete from caddie_content where caddie_id = '$cad->caddie_id' and object_id ='$expl_id' " ;
		@pmb_mysql_query($req_suppr_caddie, $dbh);
	}

	//Supression des champs perso
	if ($expl_id) {
		$p_perso=new parametres_perso("expl");
		$p_perso->delete_values($expl_id);
	}
	
	// nettoyage transfert
	$requete_suppr = "delete from transferts_demande where num_expl='$expl_id'";
	$result_suppr = pmb_mysql_query($requete_suppr);
	
	// nettoyage indexation concepts
	if ($expl_id) {
		$index_concept = new index_concept($expl_id, TYPE_EXPL);
		$index_concept->delete();
	}
	
	print "<div class='row'><div class='msg-perio'>".$msg[maj_encours]."</div></div>";
	$id_form = md5(microtime());
	$retour = "./catalog.php?categ=isbd&id=$id";
	print "<form class='form-$current_module' name=\"dummy\" method=\"post\" action=\"$retour\" style=\"display:none\">
		<input type=\"hidden\" name=\"id_form\" value=\"$id_form\">
		</form>
		<script type=\"text/javascript\">document.dummy.submit();</script>
		</div>";
}
?>	