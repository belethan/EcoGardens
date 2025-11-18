<?php

namespace App\DataFixtures;

use App\Entity\conseil;
use App\Entity\TempsConseil;
use App\Entity\User;
use App\Faker\Provider\EcoGardensProvider;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;

class ConseilFixtures extends Fixture
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Ajout du provider EcoGardens
        $faker->addProvider(new EcoGardensProvider($faker));

        // Récupérer les utilisateurs existants
        $users = $this->em->getRepository(User::class)->findAll();

        if (empty($users)) {
            echo "\n️ Aucun utilisateur trouvé en base. "
                . "Les conseils ne seront pas créés.\n";
            return;
        }

        // ⃣ Créer plusieurs conseils basés sur le vocabulaire des plantes
        for ($i = 1; $i <= 8; $i++) {
            $conseil = new conseil();

            if (method_exists($conseil, 'setTitre')) {
                $conseil->setTitre("Conseil EcoGardens n°{$i}");
            }

            // 🌿 Génère 3 phrases de 15 mots chacune avec le provider EcoGardens
            $texte = $faker->plantParagraph(3, 15);
            $conseil->setContenu($texte);

            $conseil->setCreatedAt(
                \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now'))
            );

            $conseil->setUpdatedAt(new \DateTimeImmutable());
            $conseil->setUser($faker->randomElement($users));

            $manager->persist($conseil);

            //  Crée entre 1 et 2 périodes associées (mois/année)
            $nbPeriodes = random_int(1, 2);
            for ($j = 0; $j < $nbPeriodes; $j++) {
                $mois = random_int(1, 12);
                $annee = random_int(2023, 2025);

                $temps = new tempsConseil();
                $temps->setMois($mois);
                $temps->setAnnee($annee);
                $temps->setConseil($conseil);

                $manager->persist($temps);
            }
        }

        $manager->flush();

        echo "\n Conseils EcoGardens créés avec succès à partir des utilisateurs existants !\n";
    }
}
