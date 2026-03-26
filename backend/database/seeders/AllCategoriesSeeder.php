<?php

namespace Database\Seeders;

use App\Models\CategoryNew;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AllCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $languages = Language::whereIn('code', ['ES', 'EN', 'FR', 'DE', 'PT', 'IT'])->get()->keyBy('code');
        $count = 0;

        // Turismo Cultural
        $cat = CategoryNew::create(['code' => 'turismo-cultural', 'active' => true]);
        $cat->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Cultural', 'slug' => 'turismo-cultural', 'description' => 'Turismo Cultural']);
        $cat->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Cultural Tourism', 'slug' => 'cultural-tourism', 'description' => 'Cultural Tourism']);
        $cat->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Culturel', 'slug' => 'tourisme-culturel', 'description' => 'Tourisme Culturel']);
        $cat->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Kulturtourismus', 'slug' => 'kulturtourismus', 'description' => 'Kulturtourismus']);
        $cat->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Cultural', 'slug' => 'turismo-cultural', 'description' => 'Turismo Cultural']);
        $cat->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Culturale', 'slug' => 'turismo-culturale', 'description' => 'Turismo Culturale']);

        // Turismo Vivencial
        $cat1 = CategoryNew::create(['code' => 'turismo-vivencial', 'active' => true]);
        $cat1->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Vivencial', 'slug' => 'turismo-vivencial', 'description' => 'Turismo Vivencial']);
        $cat1->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Living Tourism', 'slug' => 'living-tourism', 'description' => 'Living Tourism']);
        $cat1->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Vivenciel', 'slug' => 'tourisme-vivenciel', 'description' => 'Tourisme Vivenciel']);
        $cat1->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Erlebnistourismus', 'slug' => 'erlebnistourismus', 'description' => 'Erlebnistourismus']);
        $cat1->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Vivencial', 'slug' => 'turismo-vivencial', 'description' => 'Turismo Vivencial']);
        $cat1->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Esperienziale', 'slug' => 'turismo-esperienziale', 'description' => 'Turismo Esperienziale']);

        // Turismo Rural
        $cat2 = CategoryNew::create(['code' => 'turismo-rural', 'active' => true]);
        $cat2->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Rural', 'slug' => 'turismo-rural', 'description' => 'Turismo Rural']);
        $cat2->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Rural Tourism', 'slug' => 'rural-tourism', 'description' => 'Rural Tourism']);
        $cat2->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Rural', 'slug' => 'tourisme-rural', 'description' => 'Tourisme Rural']);
        $cat2->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Landtourismus', 'slug' => 'landtourismus', 'description' => 'Landtourismus']);
        $cat2->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Rural', 'slug' => 'turismo-rural', 'description' => 'Turismo Rural']);
        $cat2->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Rurale', 'slug' => 'turismo-rurale', 'description' => 'Turismo Rurale']);

        // Turismo Místico
        $cat3 = CategoryNew::create(['code' => 'turismo-mistico', 'active' => true]);
        $cat3->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Místico', 'slug' => 'turismo-mistico', 'description' => 'Turismo Místico']);
        $cat3->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Mystical Tourism', 'slug' => 'mystical-tourism', 'description' => 'Mystical Tourism']);
        $cat3->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Mystique', 'slug' => 'tourisme-mystique', 'description' => 'Tourisme Mystique']);
        $cat3->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Mystischer Tourismus', 'slug' => 'mystischer-tourismus', 'description' => 'Mystischer Tourismus']);
        $cat3->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Místico', 'slug' => 'turismo-mistico', 'description' => 'Turismo Místico']);
        $cat3->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Mistico', 'slug' => 'turismo-mistico', 'description' => 'Turismo Mistico']);

        // Turismo Histórico
        $cat4 = CategoryNew::create(['code' => 'turismo-historico', 'active' => true]);
        $cat4->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Histórico', 'slug' => 'turismo-historico', 'description' => 'Turismo Histórico']);
        $cat4->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Historical Tourism', 'slug' => 'historical-tourism', 'description' => 'Historical Tourism']);
        $cat4->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Historique', 'slug' => 'tourisme-historique', 'description' => 'Tourisme Historique']);
        $cat4->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Historischer Tourismus', 'slug' => 'historischer-tourismus', 'description' => 'Historischer Tourismus']);
        $cat4->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Histórico', 'slug' => 'turismo-historico', 'description' => 'Turismo Histórico']);
        $cat4->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Storico', 'slug' => 'turismo-storico', 'description' => 'Turismo Storico']);

        // Turismo Religioso
        $cat5 = CategoryNew::create(['code' => 'turismo-religioso', 'active' => true]);
        $cat5->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Religioso', 'slug' => 'turismo-religioso', 'description' => 'Turismo Religioso']);
        $cat5->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Religious Tourism', 'slug' => 'religious-tourism', 'description' => 'Religious Tourism']);
        $cat5->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Religieux', 'slug' => 'tourisme-religieux', 'description' => 'Tourisme Religieux']);
        $cat5->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Religiöser Tourismus', 'slug' => 'religioser-tourismus', 'description' => 'Religiöser Tourismus']);
        $cat5->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Religioso', 'slug' => 'turismo-religioso', 'description' => 'Turismo Religioso']);
        $cat5->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Religioso', 'slug' => 'turismo-religioso', 'description' => 'Turismo Religioso']);

        // Turismo Arqueológico
        $cat6 = CategoryNew::create(['code' => 'turismo-arqueologico', 'active' => true]);
        $cat6->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Arqueológico', 'slug' => 'turismo-arqueologico', 'description' => 'Turismo Arqueológico']);
        $cat6->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Archaeological Tourism', 'slug' => 'archaeological-tourism', 'description' => 'Archaeological Tourism']);
        $cat6->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Archéologique', 'slug' => 'tourisme-archeologique', 'description' => 'Tourisme Archéologique']);
        $cat6->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Archäologischer Tourismus', 'slug' => 'archaologischer-tourismus', 'description' => 'Archäologischer Tourismus']);
        $cat6->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Arqueológico', 'slug' => 'turismo-arqueologico', 'description' => 'Turismo Arqueológico']);
        $cat6->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Archeologico', 'slug' => 'turismo-archeologico', 'description' => 'Turismo Archeologico']);

        // Turismo Etnográfico
        $cat7 = CategoryNew::create(['code' => 'turismo-etnografico', 'active' => true]);
        $cat7->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Etnográfico', 'slug' => 'turismo-etnográfico', 'description' => 'Turismo Etnográfico']);
        $cat7->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Ethnographic Tourism', 'slug' => 'ethnographic-tourism', 'description' => 'Ethnographic Tourism']);
        $cat7->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Ethnographique', 'slug' => 'tourisme-ethnographique', 'description' => 'Tourisme Ethnographique']);
        $cat7->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Ethnographischer Tourismus', 'slug' => 'ethnographischer-tourismus', 'description' => 'Ethnographischer Tourismus']);
        $cat7->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Etnográfico', 'slug' => 'turismo-etnográfico', 'description' => 'Turismo Etnográfico']);
        $cat7->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Etnografico', 'slug' => 'turismo-etnografico', 'description' => 'Turismo Etnografico']);

        // Turismo de Naturaleza
        $cat8 = CategoryNew::create(['code' => 'turismo-naturaleza', 'active' => true]);
        $cat8->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Naturaleza', 'slug' => 'turismo-de-naturaleza', 'description' => 'Turismo de Naturaleza']);
        $cat8->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Nature Tourism', 'slug' => 'nature-tourism', 'description' => 'Nature Tourism']);
        $cat8->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme de Nature', 'slug' => 'tourisme-de-nature', 'description' => 'Tourisme de Nature']);
        $cat8->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Naturtourismus', 'slug' => 'naturtourismus', 'description' => 'Naturtourismus']);
        $cat8->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Natureza', 'slug' => 'turismo-de-natureza', 'description' => 'Turismo de Natureza']);
        $cat8->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Naturalistico', 'slug' => 'turismo-naturalistico', 'description' => 'Turismo Naturalistico']);

        // Turismo de Aventura
        $cat9 = CategoryNew::create(['code' => 'turismo-aventura', 'active' => true]);
        $cat9->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Aventura', 'slug' => 'turismo-de-aventura', 'description' => 'Turismo de Aventura']);
        $cat9->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Adventure Tourism', 'slug' => 'adventure-tourism', 'description' => 'Adventure Tourism']);
        $cat9->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme d\'Aventure', 'slug' => 'tourisme-daventure', 'description' => 'Tourisme d\'Aventure']);
        $cat9->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Abenteuertourismus', 'slug' => 'abenteuertourismus', 'description' => 'Abenteuertourismus']);
        $cat9->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Aventura', 'slug' => 'turismo-de-aventura', 'description' => 'Turismo de Aventura']);
        $cat9->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo d\'Avventura', 'slug' => 'turismo-davventura', 'description' => 'Turismo d\'Avventura']);

        // Ecoturismo
        $cat10 = CategoryNew::create(['code' => 'ecoturismo', 'active' => true]);
        $cat10->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Ecoturismo', 'slug' => 'ecoturismo', 'description' => 'Ecoturismo']);
        $cat10->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Ecotourism', 'slug' => 'ecotourism', 'description' => 'Ecotourism']);
        $cat10->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Écotourisme', 'slug' => 'Écotourisme', 'description' => 'Écotourisme']);
        $cat10->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Ökotourismus', 'slug' => 'Ökotourismus', 'description' => 'Ökotourismus']);
        $cat10->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Ecoturismo', 'slug' => 'ecoturismo', 'description' => 'Ecoturismo']);
        $cat10->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Ecoturismo', 'slug' => 'ecoturismo', 'description' => 'Ecoturismo']);

        // Turismo de Montaña
        $cat11 = CategoryNew::create(['code' => 'turismo-montana', 'active' => true]);
        $cat11->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Montaña', 'slug' => 'turismo-de-montana', 'description' => 'Turismo de Montaña']);
        $cat11->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Mountain Tourism', 'slug' => 'mountain-tourism', 'description' => 'Mountain Tourism']);
        $cat11->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme de Montagne', 'slug' => 'tourisme-de-montagne', 'description' => 'Tourisme de Montagne']);
        $cat11->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Bergtourismus', 'slug' => 'bergtourismus', 'description' => 'Bergtourismus']);
        $cat11->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Montanha', 'slug' => 'turismo-de-montanha', 'description' => 'Turismo de Montanha']);
        $cat11->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo di Montagna', 'slug' => 'turismo-di-montagna', 'description' => 'Turismo di Montagna']);

        // Turismo de Trekking
        $cat12 = CategoryNew::create(['code' => 'turismo-trekking', 'active' => true]);
        $cat12->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Trekking', 'slug' => 'turismo-de-trekking', 'description' => 'Turismo de Trekking']);
        $cat12->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Trekking Tourism', 'slug' => 'trekking-tourism', 'description' => 'Trekking Tourism']);
        $cat12->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme de Trekking', 'slug' => 'tourisme-de-trekking', 'description' => 'Tourisme de Trekking']);
        $cat12->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Trekking-Tourismus', 'slug' => 'trekking-tourismus', 'description' => 'Trekking-Tourismus']);
        $cat12->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Trekking', 'slug' => 'turismo-de-trekking', 'description' => 'Turismo de Trekking']);
        $cat12->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo di Trekking', 'slug' => 'turismo-di-trekking', 'description' => 'Turismo di Trekking']);

        // Turismo de Observación de Aves
        $cat13 = CategoryNew::create(['code' => 'turismo-observacion-aves', 'active' => true]);
        $cat13->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Observación de Aves', 'slug' => 'turismo-de-observacion-de-aves', 'description' => 'Turismo de Observación de Aves']);
        $cat13->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Birdwatching Tourism', 'slug' => 'birdwatching-tourism', 'description' => 'Birdwatching Tourism']);
        $cat13->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme d\'Observation d\'Oiseaux', 'slug' => 'tourisme-dobservation-doiseaux', 'description' => 'Tourisme d\'Observation d\'Oiseaux']);
        $cat13->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Vogelbeobachtungstourismus', 'slug' => 'vogelbeobachtungstourismus', 'description' => 'Vogelbeobachtungstourismus']);
        $cat13->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Observação de Aves', 'slug' => 'turismo-de-observacão-de-aves', 'description' => 'Turismo de Observação de Aves']);
        $cat13->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo di Birdwatching', 'slug' => 'turismo-di-birdwatching', 'description' => 'Turismo di Birdwatching']);

        // Turismo Fotográfico
        $cat14 = CategoryNew::create(['code' => 'turismo-fotografico', 'active' => true]);
        $cat14->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Fotográfico', 'slug' => 'turismo-fotográfico', 'description' => 'Turismo Fotográfico']);
        $cat14->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Photography Tourism', 'slug' => 'photography-tourism', 'description' => 'Photography Tourism']);
        $cat14->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Photographique', 'slug' => 'tourisme-photographique', 'description' => 'Tourisme Photographique']);
        $cat14->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Fototourismus', 'slug' => 'fototourismus', 'description' => 'Fototourismus']);
        $cat14->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Fotográfico', 'slug' => 'turismo-fotográfico', 'description' => 'Turismo Fotográfico']);
        $cat14->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Fotografico', 'slug' => 'turismo-fotografico', 'description' => 'Turismo Fotografico']);

        // Turismo Científico
        $cat15 = CategoryNew::create(['code' => 'turismo-cientifico', 'active' => true]);
        $cat15->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Científico', 'slug' => 'turismo-cientifico', 'description' => 'Turismo Científico']);
        $cat15->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Scientific Tourism', 'slug' => 'scientific-tourism', 'description' => 'Scientific Tourism']);
        $cat15->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Scientifique', 'slug' => 'tourisme-scientifique', 'description' => 'Tourisme Scientifique']);
        $cat15->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Wissenschaftstourismus', 'slug' => 'wissenschaftstourismus', 'description' => 'Wissenschaftstourismus']);
        $cat15->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Científico', 'slug' => 'turismo-cientifico', 'description' => 'Turismo Científico']);
        $cat15->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Scientifico', 'slug' => 'turismo-scientifico', 'description' => 'Turismo Scientifico']);

        // Turismo de Sol y Playa
        $cat16 = CategoryNew::create(['code' => 'turismo-sol-playa', 'active' => true]);
        $cat16->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Sol y Playa', 'slug' => 'turismo-de-sol-y-playa', 'description' => 'Turismo de Sol y Playa']);
        $cat16->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Sun and Beach Tourism', 'slug' => 'sun-and-beach-tourism', 'description' => 'Sun and Beach Tourism']);
        $cat16->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme de Soleil et Plage', 'slug' => 'tourisme-de-soleil-et-plage', 'description' => 'Tourisme de Soleil et Plage']);
        $cat16->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Sonne- und Strandtourismus', 'slug' => 'sonne--und-strandtourismus', 'description' => 'Sonne- und Strandtourismus']);
        $cat16->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Sol e Praia', 'slug' => 'turismo-de-sol-e-praia', 'description' => 'Turismo de Sol e Praia']);
        $cat16->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo di Sole e Spiaggia', 'slug' => 'turismo-di-sole-e-spiaggia', 'description' => 'Turismo di Sole e Spiaggia']);

        // Turismo Termal
        $cat17 = CategoryNew::create(['code' => 'turismo-termal', 'active' => true]);
        $cat17->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Termal', 'slug' => 'turismo-termal', 'description' => 'Turismo Termal']);
        $cat17->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Thermal Tourism', 'slug' => 'thermal-tourism', 'description' => 'Thermal Tourism']);
        $cat17->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Thermal', 'slug' => 'tourisme-thermal', 'description' => 'Tourisme Thermal']);
        $cat17->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Thermaltourismus', 'slug' => 'thermaltourismus', 'description' => 'Thermaltourismus']);
        $cat17->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Termal', 'slug' => 'turismo-termal', 'description' => 'Turismo Termal']);
        $cat17->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Termale', 'slug' => 'turismo-termale', 'description' => 'Turismo Termale']);

        // Turismo de Spa
        $cat18 = CategoryNew::create(['code' => 'turismo-spa', 'active' => true]);
        $cat18->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Spa', 'slug' => 'turismo-de-spa', 'description' => 'Turismo de Spa']);
        $cat18->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Spa Tourism', 'slug' => 'spa-tourism', 'description' => 'Spa Tourism']);
        $cat18->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme de Spa', 'slug' => 'tourisme-de-spa', 'description' => 'Tourisme de Spa']);
        $cat18->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Spa-Tourismus', 'slug' => 'spa-tourismus', 'description' => 'Spa-Tourismus']);
        $cat18->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Spa', 'slug' => 'turismo-de-spa', 'description' => 'Turismo de Spa']);
        $cat18->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo di Spa', 'slug' => 'turismo-di-spa', 'description' => 'Turismo di Spa']);

        // Turismo Romántico
        $cat19 = CategoryNew::create(['code' => 'turismo-romantico', 'active' => true]);
        $cat19->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Romántico', 'slug' => 'turismo-romántico', 'description' => 'Turismo Romántico']);
        $cat19->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Romantic Tourism', 'slug' => 'romantic-tourism', 'description' => 'Romantic Tourism']);
        $cat19->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Romantique', 'slug' => 'tourisme-romantique', 'description' => 'Tourisme Romantique']);
        $cat19->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Romantischer Tourismus', 'slug' => 'romantischer-tourismus', 'description' => 'Romantischer Tourismus']);
        $cat19->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Romântico', 'slug' => 'turismo-romantico', 'description' => 'Turismo Romântico']);
        $cat19->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Romantico', 'slug' => 'turismo-romantico', 'description' => 'Turismo Romantico']);

        // Turismo Familiar
        $cat20 = CategoryNew::create(['code' => 'turismo-familiar', 'active' => true]);
        $cat20->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Familiar', 'slug' => 'turismo-familiar', 'description' => 'Turismo Familiar']);
        $cat20->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Family Tourism', 'slug' => 'family-tourism', 'description' => 'Family Tourism']);
        $cat20->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Familial', 'slug' => 'tourisme-familial', 'description' => 'Tourisme Familial']);
        $cat20->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Familientourismus', 'slug' => 'familientourismus', 'description' => 'Familientourismus']);
        $cat20->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Familiar', 'slug' => 'turismo-familiar', 'description' => 'Turismo Familiar']);
        $cat20->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Familiare', 'slug' => 'turismo-familiare', 'description' => 'Turismo Familiare']);

        // Turismo Temático
        $cat21 = CategoryNew::create(['code' => 'turismo-tematico', 'active' => true]);
        $cat21->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Temático', 'slug' => 'turismo-temático', 'description' => 'Turismo Temático']);
        $cat21->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Theme Tourism', 'slug' => 'theme-tourism', 'description' => 'Theme Tourism']);
        $cat21->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Thématique', 'slug' => 'tourisme-thematique', 'description' => 'Tourisme Thématique']);
        $cat21->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Thementourismus', 'slug' => 'thementourismus', 'description' => 'Thementourismus']);
        $cat21->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Temático', 'slug' => 'turismo-temático', 'description' => 'Turismo Temático']);
        $cat21->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Tematico', 'slug' => 'turismo-tematico', 'description' => 'Turismo Tematico']);

        // Turismo Urbano
        $cat22 = CategoryNew::create(['code' => 'turismo-urbano', 'active' => true]);
        $cat22->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Urbano', 'slug' => 'turismo-urbano', 'description' => 'Turismo Urbano']);
        $cat22->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Urban Tourism', 'slug' => 'urban-tourism', 'description' => 'Urban Tourism']);
        $cat22->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Urbain', 'slug' => 'tourisme-urbain', 'description' => 'Tourisme Urbain']);
        $cat22->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Städtetourismus', 'slug' => 'stadtetourismus', 'description' => 'Städtetourismus']);
        $cat22->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Urbano', 'slug' => 'turismo-urbano', 'description' => 'Turismo Urbano']);
        $cat22->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Urbano', 'slug' => 'turismo-urbano', 'description' => 'Turismo Urbano']);

        // Turismo Gastronómico
        $cat23 = CategoryNew::create(['code' => 'turismo-gastronomico', 'active' => true]);
        $cat23->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Gastronómico', 'slug' => 'turismo-gastronomico', 'description' => 'Turismo Gastronómico']);
        $cat23->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Gastronomic Tourism', 'slug' => 'gastronomic-tourism', 'description' => 'Gastronomic Tourism']);
        $cat23->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Gastronomique', 'slug' => 'tourisme-gastronomique', 'description' => 'Tourisme Gastronomique']);
        $cat23->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Gastronomietourismus', 'slug' => 'gastronomietourismus', 'description' => 'Gastronomietourismus']);
        $cat23->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Gastronômico', 'slug' => 'turismo-gastronomico', 'description' => 'Turismo Gastronômico']);
        $cat23->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Gastronomico', 'slug' => 'turismo-gastronomico', 'description' => 'Turismo Gastronomico']);

        // Turismo de Compras
        $cat24 = CategoryNew::create(['code' => 'turismo-compras', 'active' => true]);
        $cat24->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Compras', 'slug' => 'turismo-de-compras', 'description' => 'Turismo de Compras']);
        $cat24->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Shopping Tourism', 'slug' => 'shopping-tourism', 'description' => 'Shopping Tourism']);
        $cat24->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme de Shopping', 'slug' => 'tourisme-de-shopping', 'description' => 'Tourisme de Shopping']);
        $cat24->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Einkaufstourismus', 'slug' => 'einkaufstourismus', 'description' => 'Einkaufstourismus']);
        $cat24->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Compras', 'slug' => 'turismo-de-compras', 'description' => 'Turismo de Compras']);
        $cat24->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo dello Shopping', 'slug' => 'turismo-dello-shopping', 'description' => 'Turismo dello Shopping']);

        // Turismo de Eventos
        $cat25 = CategoryNew::create(['code' => 'turismo-eventos', 'active' => true]);
        $cat25->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Eventos', 'slug' => 'turismo-de-eventos', 'description' => 'Turismo de Eventos']);
        $cat25->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Event Tourism', 'slug' => 'event-tourism', 'description' => 'Event Tourism']);
        $cat25->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme d\'Événements', 'slug' => 'tourisme-devenements', 'description' => 'Tourisme d\'Événements']);
        $cat25->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Eventtourismus', 'slug' => 'eventtourismus', 'description' => 'Eventtourismus']);
        $cat25->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Eventos', 'slug' => 'turismo-de-eventos', 'description' => 'Turismo de Eventos']);
        $cat25->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo degli Eventi', 'slug' => 'turismo-degli-eventi', 'description' => 'Turismo degli Eventi']);

        // Turismo de Festividades
        $cat26 = CategoryNew::create(['code' => 'turismo-festividades', 'active' => true]);
        $cat26->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Festividades', 'slug' => 'turismo-de-festividades', 'description' => 'Turismo de Festividades']);
        $cat26->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Festival Tourism', 'slug' => 'festival-tourism', 'description' => 'Festival Tourism']);
        $cat26->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme de Festivités', 'slug' => 'tourisme-de-festivites', 'description' => 'Tourisme de Festivités']);
        $cat26->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Festtourismus', 'slug' => 'festtourismus', 'description' => 'Festtourismus']);
        $cat26->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Festividades', 'slug' => 'turismo-de-festividades', 'description' => 'Turismo de Festividades']);
        $cat26->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo delle Festività', 'slug' => 'turismo-delle-festivita', 'description' => 'Turismo delle Festività']);

        // Turismo Musical
        $cat27 = CategoryNew::create(['code' => 'turismo-musical', 'active' => true]);
        $cat27->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Musical', 'slug' => 'turismo-musical', 'description' => 'Turismo Musical']);
        $cat27->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Musical Tourism', 'slug' => 'musical-tourism', 'description' => 'Musical Tourism']);
        $cat27->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Musical', 'slug' => 'tourisme-musical', 'description' => 'Tourisme Musical']);
        $cat27->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Musiktourismus', 'slug' => 'musiktourismus', 'description' => 'Musiktourismus']);
        $cat27->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Musical', 'slug' => 'turismo-musical', 'description' => 'Turismo Musical']);
        $cat27->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Musicale', 'slug' => 'turismo-musicale', 'description' => 'Turismo Musicale']);

        // Turismo Cinematográfico
        $cat28 = CategoryNew::create(['code' => 'turismo-cinematografico', 'active' => true]);
        $cat28->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Cinematográfico', 'slug' => 'turismo-cinematográfico', 'description' => 'Turismo Cinematográfico']);
        $cat28->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Film Tourism', 'slug' => 'film-tourism', 'description' => 'Film Tourism']);
        $cat28->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Cinématographique', 'slug' => 'tourisme-cinematographique', 'description' => 'Tourisme Cinématographique']);
        $cat28->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Filmtourismus', 'slug' => 'filmtourismus', 'description' => 'Filmtourismus']);
        $cat28->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Cinematográfico', 'slug' => 'turismo-cinematográfico', 'description' => 'Turismo Cinematográfico']);
        $cat28->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Cinematografico', 'slug' => 'turismo-cinematografico', 'description' => 'Turismo Cinematografico']);

        // Turismo Educativo
        $cat29 = CategoryNew::create(['code' => 'turismo-educativo', 'active' => true]);
        $cat29->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Educativo', 'slug' => 'turismo-educativo', 'description' => 'Turismo Educativo']);
        $cat29->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Educational Tourism', 'slug' => 'educational-tourism', 'description' => 'Educational Tourism']);
        $cat29->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Éducatif', 'slug' => 'tourisme-Éducatif', 'description' => 'Tourisme Éducatif']);
        $cat29->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Bildungstourismus', 'slug' => 'bildungstourismus', 'description' => 'Bildungstourismus']);
        $cat29->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Educativo', 'slug' => 'turismo-educativo', 'description' => 'Turismo Educativo']);
        $cat29->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Educativo', 'slug' => 'turismo-educativo', 'description' => 'Turismo Educativo']);

        // Turismo Académico
        $cat30 = CategoryNew::create(['code' => 'turismo-academico', 'active' => true]);
        $cat30->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Académico', 'slug' => 'turismo-academico', 'description' => 'Turismo Académico']);
        $cat30->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Academic Tourism', 'slug' => 'academic-tourism', 'description' => 'Academic Tourism']);
        $cat30->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Académique', 'slug' => 'tourisme-academique', 'description' => 'Tourisme Académique']);
        $cat30->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Akademischer Tourismus', 'slug' => 'akademischer-tourismus', 'description' => 'Akademischer Tourismus']);
        $cat30->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Acadêmico', 'slug' => 'turismo-academico', 'description' => 'Turismo Acadêmico']);
        $cat30->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Accademico', 'slug' => 'turismo-accademico', 'description' => 'Turismo Accademico']);

        // Turismo Idiomático
        $cat31 = CategoryNew::create(['code' => 'turismo-idiomatico', 'active' => true]);
        $cat31->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Idiomático', 'slug' => 'turismo-idiomático', 'description' => 'Turismo Idiomático']);
        $cat31->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Language Tourism', 'slug' => 'language-tourism', 'description' => 'Language Tourism']);
        $cat31->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Linguistique', 'slug' => 'tourisme-linguistique', 'description' => 'Tourisme Linguistique']);
        $cat31->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Sprachtourismus', 'slug' => 'sprachtourismus', 'description' => 'Sprachtourismus']);
        $cat31->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Idiomático', 'slug' => 'turismo-idiomático', 'description' => 'Turismo Idiomático']);
        $cat31->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Linguistico', 'slug' => 'turismo-linguistico', 'description' => 'Turismo Linguistico']);

        // Turismo LGBTQ+ Friendly
        $cat32 = CategoryNew::create(['code' => 'turismo-lgbtq-friendly', 'active' => true]);
        $cat32->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo LGBTQ+ Friendly', 'slug' => 'turismo-lgbtq-friendly', 'description' => 'Turismo LGBTQ+ Friendly']);
        $cat32->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'LGBTQ+ Friendly Tourism', 'slug' => 'lgbtq-friendly-tourism', 'description' => 'LGBTQ+ Friendly Tourism']);
        $cat32->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme LGBTQ+ Friendly', 'slug' => 'tourisme-lgbtq-friendly', 'description' => 'Tourisme LGBTQ+ Friendly']);
        $cat32->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'LGBTQ+ Freundlicher Tourismus', 'slug' => 'lgbtq-freundlicher-tourismus', 'description' => 'LGBTQ+ Freundlicher Tourismus']);
        $cat32->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo LGBTQ+ Friendly', 'slug' => 'turismo-lgbtq-friendly', 'description' => 'Turismo LGBTQ+ Friendly']);
        $cat32->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo LGBTQ+ Friendly', 'slug' => 'turismo-lgbtq-friendly', 'description' => 'Turismo LGBTQ+ Friendly']);

        // Turismo Corporativo
        $cat33 = CategoryNew::create(['code' => 'turismo-corporativo', 'active' => true]);
        $cat33->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Corporativo', 'slug' => 'turismo-corporativo', 'description' => 'Turismo Corporativo']);
        $cat33->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Corporate Tourism', 'slug' => 'corporate-tourism', 'description' => 'Corporate Tourism']);
        $cat33->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme d\'Entreprise', 'slug' => 'tourisme-dentreprise', 'description' => 'Tourisme d\'Entreprise']);
        $cat33->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Firmentourismus', 'slug' => 'firmentourismus', 'description' => 'Firmentourismus']);
        $cat33->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Corporativo', 'slug' => 'turismo-corporativo', 'description' => 'Turismo Corporativo']);
        $cat33->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Aziendale', 'slug' => 'turismo-aziendale', 'description' => 'Turismo Aziendale']);

        // Turismo de Negocios
        $cat34 = CategoryNew::create(['code' => 'turismo-negocios', 'active' => true]);
        $cat34->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Negocios', 'slug' => 'turismo-de-negocios', 'description' => 'Turismo de Negocios']);
        $cat34->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Business Tourism', 'slug' => 'business-tourism', 'description' => 'Business Tourism']);
        $cat34->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme d\'Affaires', 'slug' => 'tourisme-daffaires', 'description' => 'Tourisme d\'Affaires']);
        $cat34->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Geschäftstourismus', 'slug' => 'geschaftstourismus', 'description' => 'Geschäftstourismus']);
        $cat34->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Negócios', 'slug' => 'turismo-de-negocios', 'description' => 'Turismo de Negócios']);
        $cat34->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo d\'Affari', 'slug' => 'turismo-daffari', 'description' => 'Turismo d\'Affari']);

        // Turismo de Incentivos
        $cat35 = CategoryNew::create(['code' => 'turismo-incentivos', 'active' => true]);
        $cat35->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo de Incentivos', 'slug' => 'turismo-de-incentivos', 'description' => 'Turismo de Incentivos']);
        $cat35->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Incentive Tourism', 'slug' => 'incentive-tourism', 'description' => 'Incentive Tourism']);
        $cat35->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme d\'Incentive', 'slug' => 'tourisme-dincentive', 'description' => 'Tourisme d\'Incentive']);
        $cat35->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Anreiztourismus', 'slug' => 'anreiztourismus', 'description' => 'Anreiztourismus']);
        $cat35->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Incentivos', 'slug' => 'turismo-de-incentivos', 'description' => 'Turismo de Incentivos']);
        $cat35->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Incentive', 'slug' => 'turismo-incentive', 'description' => 'Turismo Incentive']);

        // Turismo Ferroviario
        $cat36 = CategoryNew::create(['code' => 'turismo-ferroviario', 'active' => true]);
        $cat36->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Ferroviario', 'slug' => 'turismo-ferroviario', 'description' => 'Turismo Ferroviario']);
        $cat36->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Railway Tourism', 'slug' => 'railway-tourism', 'description' => 'Railway Tourism']);
        $cat36->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Ferroviaire', 'slug' => 'tourisme-ferroviaire', 'description' => 'Tourisme Ferroviaire']);
        $cat36->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Eisenbahntourismus', 'slug' => 'eisenbahntourismus', 'description' => 'Eisenbahntourismus']);
        $cat36->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Ferroviário', 'slug' => 'turismo-ferroviário', 'description' => 'Turismo Ferroviário']);
        $cat36->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Ferroviario', 'slug' => 'turismo-ferroviario', 'description' => 'Turismo Ferroviario']);

        // Turismo en Bicicleta
        $cat37 = CategoryNew::create(['code' => 'turismo-bicicleta', 'active' => true]);
        $cat37->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo en Bicicleta', 'slug' => 'turismo-en-bicicleta', 'description' => 'Turismo en Bicicleta']);
        $cat37->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Cycling Tourism', 'slug' => 'cycling-tourism', 'description' => 'Cycling Tourism']);
        $cat37->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme à Vélo', 'slug' => 'tourisme-a-velo', 'description' => 'Tourisme à Vélo']);
        $cat37->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Fahrradtourismus', 'slug' => 'fahrradtourismus', 'description' => 'Fahrradtourismus']);
        $cat37->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo de Bicicleta', 'slug' => 'turismo-de-bicicleta', 'description' => 'Turismo de Bicicleta']);
        $cat37->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo in Bicicletta', 'slug' => 'turismo-in-bicicletta', 'description' => 'Turismo in Bicicletta']);

        // Turismo Deportivo
        $cat38 = CategoryNew::create(['code' => 'turismo-deportivo', 'active' => true]);
        $cat38->translations()->create(['language_id' => $languages['ES']->id, 'name' => 'Turismo Deportivo', 'slug' => 'turismo-deportivo', 'description' => 'Turismo Deportivo']);
        $cat38->translations()->create(['language_id' => $languages['EN']->id, 'name' => 'Sports Tourism', 'slug' => 'sports-tourism', 'description' => 'Sports Tourism']);
        $cat38->translations()->create(['language_id' => $languages['FR']->id, 'name' => 'Tourisme Sportif', 'slug' => 'tourisme-sportif', 'description' => 'Tourisme Sportif']);
        $cat38->translations()->create(['language_id' => $languages['DE']->id, 'name' => 'Sporttourismus', 'slug' => 'sporttourismus', 'description' => 'Sporttourismus']);
        $cat38->translations()->create(['language_id' => $languages['PT']->id, 'name' => 'Turismo Esportivo', 'slug' => 'turismo-esportivo', 'description' => 'Turismo Esportivo']);
        $cat38->translations()->create(['language_id' => $languages['IT']->id, 'name' => 'Turismo Sportivo', 'slug' => 'turismo-sportivo', 'description' => 'Turismo Sportivo']);

        $this->command->info('✓ 39 categorías creadas con 6 traducciones cada una');
    }
}
