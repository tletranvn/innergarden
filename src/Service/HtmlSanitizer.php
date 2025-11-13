<?php

namespace App\Service;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Service de sanitisation HTML contre les attaques XSS
 * Niveau 3 de sécurité : permet du HTML sûr (gras, italique, liens, etc.)
 */
class HtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        // Configuration sécurisée : permet uniquement les balises HTML sûres
        $config->set('HTML.Allowed', 'p,br,strong,em,u,a[href],ul,ol,li,blockquote');

        // Configuration des liens sûrs
        $config->set('HTML.TargetBlank', true); // Ouvre les liens dans un nouvel onglet
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);

        // Désactive le cache pour éviter les problèmes d'écriture
        $config->set('Cache.DefinitionImpl', null);

        $this->purifier = new HTMLPurifier($config);
    }

    /**
     * Nettoie le contenu HTML en supprimant les balises dangereuses
     *
     * @param string $dirtyHtml Contenu HTML potentiellement dangereux
     * @return string Contenu HTML sécurisé
     */
    public function sanitize(string $dirtyHtml): string
    {
        return $this->purifier->purify($dirtyHtml);
    }
}
