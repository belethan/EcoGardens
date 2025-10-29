<?php

namespace App\Faker\Provider;

use Faker\Provider\Base as BaseProvider;

class EcoGardensProvider extends BaseProvider
{
    private static array $plantes = [
        'tomate', 'basilic', 'rosier', 'menthe', 'lavande', 'orchidée', 'pivoine',
        'citronnier', 'chêne', 'olivier', 'geranium', 'lierre', 'fougère', 'hortensia',
        'pommier', 'cerisier', 'poirier', 'salade', 'courgette', 'citrouille',
    ];

    private static array $actions = [
        'arroser', 'tailler', 'planter', 'semis', 'fertiliser', 'désherber',
        'repiquer', 'biner', 'pailler', 'surveiller', 'récolter', 'transplanter',
        'entretenir', 'rempoter', 'protéger', 'observer', 'arroser tôt', 'éclaircir',
    ];

    private static array $conseils = [
        'favorise la floraison', 'protège des maladies', 'attire les abeilles',
        'aime le soleil', 'nécessite un sol humide', 'redoute le gel',
        'pousse mieux à l’ombre', 'requiert peu d’entretien', 'aime les sols calcaires',
        'supporte mal la sécheresse', 'demande un arrosage régulier',
        'est sensible aux parasites', 'apprécie le compost naturel',
    ];

    public function plantSentence(int $words = 12): string
    {
        $sentence = [];

        for ($i = 0; $i < $words; $i++) {
            switch (rand(1, 3)) {
                case 1:
                    $sentence[] = static::randomElement(self::$plantes);
                    break;
                case 2:
                    $sentence[] = static::randomElement(self::$actions);
                    break;
                case 3:
                    $sentence[] = static::randomElement(self::$conseils);
                    break;
            }
        }

        $result = ucfirst(implode(' ', $sentence)) . '.';
        return preg_replace('/\s+/', ' ', $result); // nettoyage espaces
    }

    public function plantParagraph(int $sentences = 3, int $wordsPerSentence = 12): string
    {
        $paragraph = [];
        for ($i = 0; $i < $sentences; $i++) {
            $paragraph[] = $this->plantSentence($wordsPerSentence);
        }
        return implode(' ', $paragraph);
    }
}

