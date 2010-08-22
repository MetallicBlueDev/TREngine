<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Profilcv_Index extends Module_Model {
	
	public function display() {
		?>
<table cellSpacing="5" cellPadding="0" border="0">
        <tbody>
        <tr>
          <td colSpan=2><br /><br /><br /><br />
            <span style="font-size: 15px;">Curriculum Vitae</span>
            <hr SIZE=1>


          </td>
        </tr>
        <tr>
	<td>
		<b>S&eacute;bastien Villemain</b><br />
		N&eacute; le 07/01/1989 - C&eacute;libataire<br />
		R&eacute;gion: Bourgogne
	</td>
	<td valign="top">
	</td>
        </tr>
        <tr><td colspan="2">
<br /><br />
<b><u>CONNAISSANCES  EN  INFORMATIQUE</u></b><br /><br />
<b>Technologies Java</b><br />

Java EE (J2EE) : JSP, Servlet, XML. Ant, JUnit.<br />
Java SE (J2SE) : Application client et server, interface graphique.<br />
Android (de Google) : Application client, interface graphique, network.<br />
Outils : NetBeans, Eclipse, UML.

<br /><br />

<b>Autres langages</b><br />
C et C++, Scripts Shell.

<br /><br />

<b>Langages de programmation web</b><br />
HTML/XHTML, CSS, JavaScript, Perl et PHP (Protocolaire et Orient&eacute; Objet).

<br /><br />

<b>Serveurs</b><br />
Apache, Tomcat.

<br /><br />

<b>Bases de donn&eacute;es</b><br />

MySQL (installation/configuration), ORACLE. Langage SQL.

<br /><br />

<b>Syst&#232;me de gestion de version</b><br />
SVN, GIT.

<br /><br />

<b>Divers</b><br />
Microsoft Office, Virtual Box, GIMP, Paint Shop Pro, MAO...


<br /><br />

<b>Domaines fonctionnels</b><br />
Applications Web, Applications pour t&eacute;l&eacute;phone portable, Applications Multiplateforme, R&eacute;seaux et T&eacute;l&eacute;com.

<br />
<br /><br />


<b><u>FORMATION</u></b><br /><br />

<b>2008 - 2010</b><br />
BTS IRIS (Informatique et R&eacute;seau Industriel pour les Services technique) au lyc&eacute;e Nic&eacute;phore Niepce &#224; Chalon-sur-Sa&#244;ne. (Moyenne de 14.5/20 avec 19/20 en projet informatique).<br />
Programmation C et C++, Programmation Java et Sevlet, Programmation Web PHP.<br />
<br />

<b>2004-2008</b><br />
Baccalaur&eacute;at STI G&eacute;nie M&eacute;canique avec mention assez bien au lyc&eacute;e Clos Maire &#224; Beaune.<br />

Option ISI : Initiation aux sciences de l'ing&eacute;nieur.<br />
Option ISP : Informatique et syst&#232;mes de production.<br />


<br /><br />
<b><u>EXPERIENCE  PROFESSIONNELLE</u></b><br /><br />

<b><u><i>Mai &#224; Juin 2009</i></u></b> - Soci&eacute;t&eacute; Daoditu - D&eacute;velopement de site web<br />

<br />
<i><b>Finalisation de sites Internet, cr&eacute;ation d'une maquette fonctionnelle et d'un site Internet.</b></i><br /><br />
D&eacute;veloppement sous Eclipse.
<br /><br />
<i>Environnement  : PHP OO, MySql, HTML/XHTML, CSS, WAMP, Eclipse.</i>
<br /><br /><br />

<b><u><i>Depuis Septembre 2007</i></u></b> - Gendarme Ajoint de R&eacute;serve<br />

<br />
<i><b>Employ&eacute; r&eacute;guli&#232;rement en qualit&eacute; de r&eacute;serviste &#224; la compagnie de gendarmerie d&eacute;partementale de Beaune.</b></i><br /><br />
<br />

<b><u><i>Juillet 2007</i></u></b> - Pr&eacute;paration militaire<br />

<br />
<i><b>Formation &#224; la pr&eacute;paration militaire de la gendarmerie.</b></i><br /><br />
<br />


<b><u>DIVERS</u></b><br /><br />

Langue : Anglais technique,<br />
Permis de conduire cat&eacute;gorie B,<br />
Compose de la musique assist&eacute;e par ordinateur (MAO).<br />

<br />

<b>Site web</b><br />
<a href="http://www.trancer-studio.net">http://www.Trancer-Studio.net</a><br />

			</td>
		</tr>
		</tbody>
   </table>
<?php
	}
	
	public function setting() {
		return "Pas de setting...";
	}
}


?>