@extends('layouts.app', ['title' => 'Mentions légales'])

@section('content')
    <style>
        .legal {
            padding: 40px 0 80px;
            display: grid;
            gap: 28px;
        }
        .legal-block {
            background: #ffffff;
            border: 1px solid rgba(17,17,17,0.08);
            border-radius: 16px;
            padding: 24px 26px;
            box-shadow: 0 8px 24px rgba(17,17,17,0.06);
        }
        .legal-block h2 {
            margin-top: 0;
            font-size: 22px;
        }
        .legal-block p {
            margin: 8px 0;
            color: #364152;
            font-size: 15px;
            line-height: 1.6;
        }
        .legal-block ul {
            margin: 12px 0 0 0;
            padding-left: 18px;
            color: #364152;
            font-size: 15px;
            line-height: 1.6;
        }
        .legal-block li {
            margin-bottom: 6px;
        }
        .legal-note {
            font-size: 13px;
            color: #7a8699;
        }
        @media (max-width: 768px) {
            .legal {
                gap: 20px;
                padding: 30px 0 60px;
            }
            .legal-block {
                padding: 20px;
            }
        }
        .page-hero--legal {
            padding: 0 0 30px;
            margin: 0 calc(50% - 50vw);
            width: 100vw;
        }
        .page-hero__inner--full {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 50px 20px 46px;
            border-radius: 0;
        }
    </style>

    <section class="page-hero page-hero--legal">
        <div class="page-hero__inner page-hero__inner--full">
            <h1>Mentions légales</h1>
            <p>Cette page précise l'identité de l'éditeur du site ripair.shop et les règles applicables à son utilisation.</p>
        </div>
    </section>

    <section class="container legal">
        <article class="legal-block">
            <h2>1. Éditeur du site</h2>
            <p><strong>RIPAIR</strong><br>
                <strong>Forme juridique</strong> : Entreprise individuelle<br>
                <strong>Siège social</strong> : 117 allée du Levant, 40300 Peyrehorade, France<br>
                <strong>Immatriculation</strong> : RCS de Dax sous le numéro 923&nbsp;871&nbsp;982.</p>
            <p><strong>Numéro de TVA intracommunautaire</strong> : FR&nbsp;38&nbsp;923&nbsp;871&nbsp;982.</p>
            <p><strong>Directeur de la publication</strong> : Maxence Cordeau.</p>
            <p><strong>Contact</strong> : contact@ripair.shop - 06&nbsp;15&nbsp;58&nbsp;87&nbsp;82.</p>
        </article>

        <article class="legal-block">
            <h2>2. Hébergement</h2>
            <p>Le site est hébergé par :</p>
            <p><strong>OVHcloud</strong><br>
                2, rue Kellermann, 59100 Roubaix, France<br>
                Tél. : +33&nbsp;9&nbsp;72&nbsp;10&nbsp;10&nbsp;07.</p>
        </article>

        <article class="legal-block">
            <h2>3. Activité</h2>
            <p><strong>RIPAIR</strong> propose des services de réparation et de remise en état de smartphones, tablettes et ordinateurs pour les particuliers comme pour les professionnels.</p>
            <p>Les tarifs et disponibilités indiqués sur le site sont donnés à titre indicatif et peuvent être ajustés après diagnostic en atelier.</p>
        </article>

        <article class="legal-block">
            <h2>4. Propriété intellectuelle</h2>
            <p>L'ensemble des contenus publiés sur le site (textes, images, visuels, logos, vidéos, charte graphique, etc.) est la propriété exclusive de <strong>RIPAIR</strong> ou fait l'objet d'une autorisation d'utilisation.</p>
            <p>Toute reproduction, représentation, modification, publication, adaptation ou exploitation, totale ou partielle, de ces contenus, quel que soit le moyen ou le procédé, est interdite sans l'accord préalable et écrit de <strong>RIPAIR</strong>.</p>
        </article>

        <article class="legal-block">
            <h2>5. Données personnelles et cookies</h2>
            <p>Les informations collectées via les formulaires (devis, rendez-vous, contact) sont indispensables au traitement des demandes. Elles sont exclusivement destinées à <strong>RIPAIR</strong> et ne sont pas cédées à des tiers.</p>
            <p>Conformément au Règlement (UE) 2016/679 et à la loi Informatique et Libertés, vous disposez d'un droit d'accès, de rectification, d'opposition, d'effacement et de portabilité des données vous concernant. Pour exercer vos droits, adressez votre demande à contact@ripair.shop en précisant l'objet de la requête et en joignant un justificatif d'identité.</p>
            <p>La navigation sur le site peut entraîner le dépôt de cookies strictement nécessaires au fonctionnement des services. Aucun cookie publicitaire n'est installé sans votre consentement préalable.</p>
        </article>

        <article class="legal-block">
            <h2>6. Responsabilité</h2>
            <p><strong>RIPAIR</strong> s'efforce de fournir sur le site ripair.shop des informations exactes et récemment mises à jour. Toutefois, des erreurs ou omissions peuvent subsister. L'utilisateur est invité à vérifier les informations essentielles et à signaler toute anomalie à contact@ripair.shop.</p>
            <p><strong>RIPAIR</strong> ne saurait être tenue responsable des dommages directs ou indirects consécutifs à l'accès au site, à son utilisation ou aux informations qui y figurent.</p>
        </article>

        <article class="legal-block">
            <h2>7. Service après-vente</h2>
            <p><strong>RIPAIR</strong> assure directement le suivi de ses prestations via son service client. Pour toute question ou réclamation, contactez-nous à contact@ripair.shop ou par téléphone au 06&nbsp;15&nbsp;58&nbsp;87&nbsp;82.</p>
        </article>

        <article class="legal-block">
            <h2>8. Droit applicable</h2>
            <p>Le site et les présentes mentions légales sont soumis au droit français. En cas de litige et à défaut d'accord amiable, les tribunaux compétents seront ceux du ressort du siège social de <strong>RIPAIR</strong>.</p>
            <p class="legal-note">Dernière mise à jour : 22 octobre 2025.</p>
        </article>
    </section>
@endsection
