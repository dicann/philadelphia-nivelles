# Backlog — Église Philadelphia Nivelles

Site live sur https://philadelphia-nivelles.be (formulaire de contact fonctionnel, envoi par SMTP).
Ce qui reste à faire, par ordre d'importance :

## Dons en ligne
- [ ] Compte Stripe au nom de l'ASBL (Centre Évangélique Roumain, BCE 1024.526.965), IBAN de l'ASBL
- [ ] Clés de test + webhook `https://philadelphia-nivelles.be/webhook-stripe.php` dans `config.php`, paiement test (4242 4242 4242 4242)
- [ ] Passage en live (clés live, nouveau webhook live, `'payment_mode' => 'live'`)

## Contenu (à fournir par l'église)
- [ ] QR code Payconiq → `uploads/images/payconiq.png`
- [ ] Photos de la communauté (cultes à Genappe, chorale, jeunes, enfants) pour la page Communauté et les activités
- [ ] Lien Instagram (quand le compte existera) → `contact.instagram`
- [ ] Heure et lieu du concert de Noël, puis réactiver les actualités (`"actualites_actives": true`, ajouter `"actualites"` au menu)
- [ ] Budgets des prochaines étapes du projet
- [ ] Validation du texte « Ce que nous croyons » par les responsables

## Technique
- [ ] Google Search Console + sitemap `https://philadelphia-nivelles.be/sitemap.xml`
- [ ] Supprimer le sous-domaine de test (work.philadelphia-nivelles.be) dans cPanel
- [ ] (Option) retirer la photo de groupe de l'historique Git
