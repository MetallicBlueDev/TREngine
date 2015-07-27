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
            <span class="text_bold">S&eacute;bastien Villemain</span><br />
            N&eacute; le 07/01/1989 - C&eacute;libataire<br />
            R&eacute;gion: Bourgogne
        </div>

        <br /><br />
        <div class="title"><span class="text_bold"><span class="text_underline">CONNAISSANCES  EN  INFORMATIQUE</span></span></div><br /><br />
        <div class="description" style="width: 80%;">
            <span><span class="text_bold">Technologies Java</span><br /></span>

            Java EE (J2EE) : JSP, Servlet, XML. Ant, JUnit.<br />
            Java SE (J2SE) : Application client et server, interface graphique.<br />
            Android (de Google) : Application client, interface graphique...<br />
            Outils : NetBeans, Eclipse, UML.

            <br /><br />

            <span><span class="text_bold">Autres langages</span></span><br />
            C et C++, Scripts Shell.

            <br /><br />

            <span><span class="text_bold">Langages de programmation web</span></span><br />
            HTML/XHTML, CSS, JavaScript, Perl et PHP (Protocolaire et Orient&eacute; Objet).

            <br /><br />

            <span><span class="text_bold">Serveurs</span></span><br />
            Apache, Tomcat.

            <br /><br />

            <span><span class="text_bold">Bases de donn&eacute;es</span></span><br />

            MySQL (installation/configuration), ORACLE. Langage SQL.

            <br /><br />

            <span><span class="text_bold">Syst&#232;me de gestion de version</span></span><br />
            SVN, GIT.

            <br /><br />

            <span><span class="text_bold">Divers</span></span><br />
            Microsoft Office, Virtual Box, GIMP, Paint Shop Pro, MAO...


            <br /><br />

            <span><span class="text_bold">Domaines fonctionnels</span></span><br />
            Applications Web, Applications pour t&eacute;l&eacute;phone portable, Applications Multiplateforme, R&eacute;seaux et T&eacute;l&eacute;com.
        </div>

        <br />
        <br /><br />

        <div class="title"><span class="text_bold"><span class="text_underline">FORMATION</span></span></div><br /><br />
        <div class="description" style="width: 80%;">
            <span><span class="text_bold">2008 - 2010</span></span><br />

            BTS IRIS (Informatique et R&eacute;seau Industriel pour les Services technique) au lyc&eacute;e Nic&eacute;phore Niepce &#224; Chalon-sur-Sa&#244;ne.<br />
            (Moyenne de 14.5/20 avec 19/20 en projet informatique).<br /><br />
            Programmation C et C++, Programmation Java et Sevlet, Programmation Web PHP.<br /><br />

            <span><span class="text_bold">2004-2008</span></span><br />
            Baccalaur&eacute;at STI G&eacute;nie M&eacute;canique avec mention assez bien au lyc&eacute;e Clos Maire &#224; Beaune.<br /><br />

            Option ISI : Initiation aux sciences de l'ing&eacute;nieur.<br />
            Option ISP : Informatique et syst&#232;mes de production.<br />
        </div>

        <br /><br /><br />

        <div class="title"><span class="text_bold"><span class="text_underline">EXPERIENCE  PROFESSIONNELLE</span></span></div><br /><br />
        <div class="description" style="width: 80%;">
            <span class="text_bold"><span class="text_underline"><span class="text_italic">Mai &#224; Juin 2009</span></span> - Soci&eacute;t&eacute; Daoditu - D&eacute;velopement de site web.</span><br />

            <br />
            <span class="text_underline"><span class="text_bold">Finalisation de sites Internet, cr&eacute;ation d'une maquette fonctionnelle et d'un site Internet.</span></span><br /><br />
            D&eacute;veloppement sous Eclipse.
            <br /><br />
            <span class="text_underline">Environnement  : PHP OO, MySql, HTML/XHTML, CSS, WAMP, Eclipse.</span>
            <br /><br /><br />

            <span class="text_bold"><span class="text_underline"><span class="text_italic">Depuis Septembre 2007</span></span> - Gendarme Ajoint de R&eacute;serve.</span><br />

            <br />
            <span class="text_underline"><span class="text_bold">Employ&eacute; r&eacute;guli&#232;rement en qualit&eacute; de r&eacute;serviste &#224; la compagnie de gendarmerie d&eacute;partementale de Beaune.</span></span><br /><br />
            <br />

            <span class="text_bold"><span class="text_underline"><span class="text_italic">Juillet 2007</span></span> - Pr&eacute;paration militaire.</span><br />

            <br />
            <span class="text_underline"><span class="text_bold">Formation &#224; la pr&eacute;paration militaire de la gendarmerie.</span></span><br />
        </div>

        <br /><br />

        <div class="title"><span class="text_bold"><span class="text_underline">DIVERS</span></span></div><br /><br />
        <div class="description" style="width: 80%;">
            Langue : Anglais technique,<br />
            Permis de conduire cat&eacute;gorie B,<br />
            Compose de la musique assist&eacute;e par ordinateur (MAO).<br />

            <br />

            <span><span class="text_bold">Site web</span></span><br />
            <a href="http://www.trancer-studio.net">Site officiel Trancer-Studio</a><br />
            <a href="http://www.jamendo.com/fr/artist/Trancer">Musiques publi&eacute;es sur Jamendo</a><br />
        </div>
        <?php
    }

    public function setting() {
        return "Pas de setting...";
    }

}
