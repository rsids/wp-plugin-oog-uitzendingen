<?php
/**
 * Created by PhpStorm.
 * User: ids
 * Date: 10-11-16
 * Time: 9:38
 */

namespace oog\uitzendingen;


class Uitzending
{

    const ACTION_CONNECT = 'uitzendingen_google_connect';
    const ACTION_DISCONNECT = 'uitzendingen_google_disconnect';
    const ACTION_STORE_CODE = 'uitzendingen_google_store_code';
    const ACTION_REDIRECT = 'uitzendingen_google_redirect';
    const ACTION_GET_CATEGORIES = 'uitzendingen_google_get_categories';
    const NONCE = 'uitzendingen_nonce';

    const GOOGLE_PROJECT_ID = 'youtube-video-uploader-149022';

    const POST_TYPE_TV = 'uitzending-tv';
    const POST_TYPE_RADIO = 'uitzending-radio';

    const TAXONOMY_PROGRAMME = 'uitzending-programma';
    const TAXONOMY_CATEGORY = 'uitzending-category';

    const OPTION_CATEGORIES = 'oog-uitzending-categories';

    const NOTICE_CATEGORY_OK = 1;
    const NOTICE_CATEGORY_ERR = 2;
    const NOTICE_YOUTUBE_FAIL = 3;

    static function GetDefaultProgrammes()
    {
        return [
            '4 Mijl van Groningen',
            'Aan tafel met Maarten',
            'Achter de rooilijn',
            'Achter Graven Verborgen',
            'Achtertuin van Groningen',
            'Bakfietsbabbel',
            'Baksteen',
            'Beno',
            'Beno\'s Stad',
            'Bevrijdingsfestival',
            'Club van 100',
            'Cultuur050',
            'Dagdoorbraak',
            'De Leukste Oranjestraat',
            'De Leukste Straat van Groningen',
            'De Zeven Zonden',
            'Dodenherdenking',
            'Ebbingekwartier',
            'Een Dag uit Duizend Dromen',
            'Een goed idee!',
            'EK Journaal',
            'ErOpUit',
            'Eurosonic / Noorderslag',
            'ForumCafé',
            'Gat in de Markt',
            'Geen Sores',
            'Groningen te koop',
            'Groningse Straatkunstprijs 2011',
            'Groots in Groningen',
            'Heel gewoon Cathy en Gre',
            'Herestraat Helemaal',
            'Het Blauwe dorp',
            'Het Geheim van Groningen',
            'Het zal mijn tijd wel duren',
            'Hobby Hoppen',
            'Hou Wortol?',
            'IFFR in Groningen',
            'In bed met Maarten',
            'Jaaroverzicht nieuws',
            'KEI-week',
            'Kerstwensen',
            'Klei',
            'Komen en Gaan',
            'Kunst en Cultuur',
            'Lekker op Stee',
            'Let’s Gro',
            'Levenslust',
            'Loep',
            'Mamamini',
            'Mijn Uitvaart',
            'Monumentje voor jezelf',
            'Nachtbrakers',
            'Nieuws',
            'Ogentroost &amp; Liefdegras',
            'OOG Actueel',
            'OOG Forum',
            'OOG Podium',
            'OOG Sport',
            'OOG TV 25 jaar',
            'OPdeRIT',
            'Over oorlog',
            'Piet op het Nieuws',
            'Plons!',
            'Popeye',
            'Pronkjewailn',
            'Radionieuws',
            'Rondje Stad',
            'Senioren TV',
            'Specials',
            'Stad Vandaag',
            'Stadjers',
            'StadjersTV Toppers',
            'Stadskracht',
            'Stadsmomenten',
            'Stand van Stad',
            'Studentenhuis in Actie',
            'STUG',
            'Tegels eruit, tuin erin',
            'Teruggespoeld',
            'Test',
            'Toekomst van Groningen',
            'Tofja',
            'Tussen Hoog en Hoger',
            'Urbanizm',
            'Valentijnswensen',
            'Van Dijken',
            'Van Geen Wijken Weten',
            'Veilige Huisjes',
            'Videoclips',
            'Waterlooshow',
            'Week van de geschiedenis',
            'Weekoverzicht nieuws',
            'Wereldburgers',
            'WK Journaal',
        ];
    }
}