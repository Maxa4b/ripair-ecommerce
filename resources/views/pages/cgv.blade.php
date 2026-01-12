@extends('layouts.app', ['title' => 'Conditions générales de vente'])

@section('content')
    <style>
        .cgv {
            padding: 40px 0 80px;
            display: grid;
            gap: 28px;
        }
        .cgv-block {
            background: #ffffff;
            border: 1px solid rgba(17,17,17,0.08);
            border-radius: 16px;
            padding: 24px 26px;
            box-shadow: 0 8px 24px rgba(17,17,17,0.06);
        }
        .cgv-block h2 {
            margin-top: 0;
            font-size: 20px;
        }
        .cgv-block p, .cgv-block ul {
            margin: 8px 0;
            color: #364152;
            font-size: 15px;
            line-height: 1.6;
        }
        .cgv-block ul {
            padding-left: 18px;
        }
        .cgv-block li {
            margin-bottom: 6px;
        }
        .cgv-note {
            font-size: 13px;
            color: #7a8699;
        }
        @media (max-width: 768px) {
            .cgv {
                gap: 20px;
                padding: 30px 0 60px;
            }
            .cgv-block {
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
            <h1>Conditions générales de vente</h1>
            <p>Applicables aux ventes de pièces, accessoires et prestations proposées par RIPAIR via la boutique en ligne.</p>
        </div>
    </section>

    <section class="container cgv">
        <article class="cgv-block">
            <h2>Préambule</h2>
            <p>Les présentes conditions générales de vente (CGV) régissent les ventes effectuées sur la boutique en ligne de <strong>RIPAIR</strong>, entreprise individuelle, 117 allée du Levant, 40300 Peyrehorade, France, RCS Dax n° 923 871 982, TVA intracommunautaire FR 38 923 871 982. Contact : contact@ripair.shop – 06 15 58 87 82.</p>
        </article>

        <article class="cgv-block">
            <h2>Définitions</h2>
            <ul>
                <li><strong>Boutique en ligne</strong> : site boutique.ripair.shop où les produits sont présentés et vendus.</li>
                <li><strong>Produits</strong> : pièces détachées, accessoires et prestations proposés par RIPAIR.</li>
                <li><strong>Commande</strong> : contrat de vente conclu entre RIPAIR et le client via le site.</li>
                <li><strong>Client</strong> : consommateur ou professionnel achetant sur la boutique.</li>
                <li><strong>Transaction</strong> : opérations de paiement (carte via Stripe, PayPal).</li>
            </ul>
        </article>

        <article class="cgv-block">
            <h2>Article 1 - Objet</h2>
            <p>Les présentes CGV définissent les conditions dans lesquelles RIPAIR vend ses produits en ligne. Elles prévalent sur toute autre condition, sauf accord écrit contraire.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 2 - Acceptation des conditions</h2>
            <p>La validation de la commande vaut acceptation sans réserve des CGV. Le client déclare en avoir pris connaissance avant le paiement.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 3 - Produits</h2>
            <p>Les caractéristiques essentielles des produits sont indiquées sur les fiches. Certains produits peuvent ne pas être disponibles en ligne. Les photos sont non contractuelles.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 4 - Commande</h2>
            <p>Les systèmes d’enregistrement automatiques valent preuve de la commande. RIPAIR confirme l’acceptation par email. RIPAIR peut refuser ou annuler une commande en cas de fraude, défaut de paiement ou litige antérieur. Le client est responsable des informations saisies (adresse, contact).</p>
        </article>

        <article class="cgv-block">
            <h2>Article 5 - Prix</h2>
            <p>Les prix sont indiqués en euros TTC hors frais de livraison. Les frais de port sont précisés avant validation. Les prix applicables sont ceux en vigueur lors de la commande.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 6 - Paiement</h2>
            <p>Le paiement est exigible à la commande. Moyens acceptés : carte bancaire (Stripe) et PayPal. La commande est effective après accord du centre de paiement. RIPAIR ne stocke pas les données bancaires.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 7 - Livraison</h2>
            <p>Modes de livraison : domicile, relais ou transporteurs proposés au checkout. Les délais sont indicatifs ; aucun dédommagement en cas de retard < 6 jours ouvrés. Le risque est transféré au client à la remise au transporteur. Le client doit vérifier le colis à réception et formuler des réserves précises dans les 48h.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 8 - Droit de rétractation</h2>
            <p>Le client dispose de 14 jours après réception pour exercer son droit de rétractation (sauf exceptions légales : prestations déjà exécutées, biens scellés ouverts, etc.). Les produits doivent être retournés neufs, non montés, dans l’emballage d’origine, avec accessoires et notices. Les frais de retour sont à la charge du client. Remboursement sous 14 jours après contrôle du retour. Pas de bon de retour prépayé en cas d’erreur client.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 9 - Réserve de propriété</h2>
            <p>RIPAIR reste propriétaire des produits jusqu’au paiement intégral. Les risques sont transférés au client à la remise au transporteur.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 10 - Garanties</h2>
            <p>Les produits bénéficient des garanties légales de conformité et vices cachés. Certaines pièces peuvent avoir une garantie commerciale indiquée sur la fiche produit ; elle exclut la casse, l’oxydation, les montages incorrects ou usages non conformes. Tout retour doit être complet, dans l’emballage d’origine, sans marquage.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 11 - SAV</h2>
            <p>Contact SAV : contact@ripair.shop ou 06 15 58 87 82. Selon le cas, un échange, un bon d’achat ou un remboursement peut être proposé. Des photos, diagnostics ou retours peuvent être demandés.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 12 - Responsabilité</h2>
            <p>RIPAIR ne peut être tenue responsable des dommages indirects, pertes de données ou manque à gagner liés à l’usage du site ou des produits. La responsabilité est en tout état de cause limitée au montant de la commande.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 13 - Données personnelles</h2>
            <p>Les données collectées sont utilisées pour la gestion des commandes et la relation client. Elles peuvent être transmises aux partenaires nécessaires (paiement, transport). Conformément au RGPD, droits d’accès, rectification, opposition, effacement et portabilité sur demande à contact@ripair.shop.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 14 - Propriété intellectuelle</h2>
            <p>Les contenus du site (textes, visuels, logos) sont la propriété de RIPAIR ou utilisés avec autorisation. Toute reproduction non autorisée est interdite.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 15 - Médiation</h2>
            <p>En cas de litige, le client peut saisir gratuitement un médiateur de la consommation après réclamation écrite restée sans réponse satisfaisante. Coordonnées du médiateur disponibles sur demande.</p>
        </article>

        <article class="cgv-block">
            <h2>Article 16 - Droit applicable et juridiction</h2>
            <p>Les présentes CGV sont soumises au droit français. À défaut de solution amiable, les tribunaux du ressort du siège de RIPAIR seront compétents.</p>
            <p class="cgv-note">Dernière mise à jour : {{ now()->format('d/m/Y') }}.</p>
        </article>
    </section>
@endsection
