<?php

namespace TREngine\Custom\Module\ModuleCustomProfilcv;

use TREngine\Engine\Module\ModuleModel;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

class ModuleIndex extends ModuleModel {

    public function display() {
        ?>

        <div class="title"><span>Curriculum Vitae</span></div>
        <br />
        <div class="description" style="width: 40%;">
            <b>S&eacute;bastien Villemain</b><br />
            N&eacute; le 07/01/1989 - C&eacute;libataire<br />
            R&eacute;gion: Bourgogne
        </div>

        <br /><br />
        <div class="title"><span><b><u>CONNAISSANCES  EN  INFORMATIQUE</u></b></span></div><br /><br />
        <div class="description" style="width: 80%;">
            <span><b>Technologies Java</b><br /></span>

            Java EE (J2EE) : JSP, Servlet, XML. Ant, JUnit.<br />
            Java SE (J2SE) : Application client et server, interface graphique.<br />
            Android (de Google) : Application client, interface graphique...<br />
            Outils : NetBeans, Eclipse, UML.

            <br /><br />

            <span><b>Autres langages</b></span><br />
            C et C++, Scripts Shell.

            <br /><br />

            <span><b>Langages de programmation web</b></span><br />
            HTML/XHTML, CSS, JavaScript, Perl et PHP (Protocolaire et Orient&eacute; Objet).

            <br /><br />

            <span><b>Serveurs</b></span><br />
            Apache, Tomcat.

            <br /><br />

            <span><b>Bases de donn&eacute;es</b></span><br />

            MySQL (installation/configuration), ORACLE. Langage SQL.

            <br /><br />

            <span><b>Syst&#232;me de gestion de version</b></span><br />
            SVN, GIT.

            <br /><br />

            <span><b>Divers</b></span><br />
            Microsoft Office, Virtual Box, GIMP, Paint Shop Pro, MAO...


            <br /><br />

            <span><b>Domaines fonctionnels</b></span><br />
            Applications Web, Applications pour t&eacute;l&eacute;phone portable, Applications Multiplateforme, R&eacute;seaux et T&eacute;l&eacute;com.
        </div>

        <br />
        <br /><br />

        <div class="title"><span><b><u>FORMATION</u></b></span></div><br /><br />
        <div class="description" style="width: 80%;">
            <span><b>2008 - 2010</b></span><br />

            BTS IRIS (Informatique et R&eacute;seau Industriel pour les Services technique) au lyc&eacute;e Nic&eacute;phore Niepce &#224; Chalon-sur-Sa&#244;ne.<br />
            (Moyenne de 14.5/20 avec 19/20 en projet informatique).<br /><br />
            Programmation C et C++, Programmation Java et Sevlet, Programmation Web PHP.<br /><br />

            <span><b>2004-2008</b></span><br />
            Baccalaur&eacute;at STI G&eacute;nie M&eacute;canique avec mention assez bien au lyc&eacute;e Clos Maire &#224; Beaune.<br /><br />

            Option ISI : Initiation aux sciences de l'ing&eacute;nieur.<br />
            Option ISP : Informatique et syst&#232;mes de production.<br />
        </div>

        <br /><br /><br />

        <div class="title"><span><b><u>EXPERIENCE  PROFESSIONNELLE</u></b></span></div><br /><br />
        <div class="description" style="width: 80%;">
            <span><b><u><i>Mai &#224; Juin 2009</i></u></b> - Soci&eacute;t&eacute; Daoditu - D&eacute;velopement de site web.</span><br />

            <br />
            <i><b>Finalisation de sites Internet, cr&eacute;ation d'une maquette fonctionnelle et d'un site Internet.</b></i><br /><br />
            D&eacute;veloppement sous Eclipse.
            <br /><br />
            <i>Environnement  : PHP OO, MySql, HTML/XHTML, CSS, WAMP, Eclipse.</i>
            <br /><br /><br />

            <span><b><u><i>Depuis Septembre 2007</i></u></b> - Gendarme Ajoint de R&eacute;serve.</span><br />

            <br />
            <i><b>Employ&eacute; r&eacute;guli&#232;rement en qualit&eacute; de r&eacute;serviste &#224; la compagnie de gendarmerie d&eacute;partementale de Beaune.</b></i><br /><br />
            <br />

            <span><b><u><i>Juillet 2007</i></u></b> - Pr&eacute;paration militaire.</span><br />

            <br />
            <i><b>Formation &#224; la pr&eacute;paration militaire de la gendarmerie.</b></i><br />
        </div>

        <br /><br />

        <div class="title"><span><b><u>DIVERS</u></b></span></div><br /><br />
        <div class="description" style="width: 80%;">
            Langue : Anglais technique,<br />
            Permis de conduire cat&eacute;gorie B,<br />
            Compose de la musique assist&eacute;e par ordinateur (MAO).<br />

            <br />

            <span><b>Site web</b></span><br />
            <a href="http://www.trancer-studio.net">Site officiel Trancer-Studio</a><br />
            <a href="http://www.jamendo.com/fr/artist/Trancer">Musiques publi&eacute;es sur Jamendo</a><br />
        </div>
        <?php
    }

    public function setting() {
        return "Pas de setting...";
    }

}
