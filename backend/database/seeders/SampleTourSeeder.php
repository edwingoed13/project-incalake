<?php

namespace Database\Seeders;

use App\Models\Tour;
use App\Models\TourTranslation;
use App\Models\TourPrice;
use App\Models\TourMediaGallery;
use App\Models\Nationality;
use App\Models\AgeStage;
use Illuminate\Database\Seeder;

class SampleTourSeeder extends Seeder
{
    public function run(): void
    {
        // Create a sample tour with full data
        $tour = Tour::create([
            'code' => 'LAKE001',
            'primary_language_id' => 1,
            'city_id' => 1,
            'city_name' => 'Puno',
            'service_type' => 'tour',
            'difficulty' => 'easy',
            'target_audience' => 'all',
            'status' => 'published',
            'active' => true,
            'capacity' => 50,
            'cupos' => 50,
            'duration_days' => 1,
            'duration_hours' => 8,
            'departure_time' => '07:00:00',
            'departure_period' => 'AM',
            'timezone' => 'America/Lima',
            'booking_anticipation_hours' => 24,
            'payment_method' => json_encode(['online', 'cash']),
            'data_requirement' => json_encode(['passport', 'id_card']),
            'index_status' => true,
            'follow_status' => true,
        ]);

        // Add translation
        TourTranslation::create([
            'tour_id' => $tour->id,
            'language_id' => 1,
            'h1_title' => 'Tour al Lago Titicaca - Islas Flotantes de los Uros',
            'slug' => 'tour-lago-titicaca-islas-uros',
            'meta_title' => 'Tour Lago Titicaca | Islas Flotantes Uros | Desde Puno',
            'meta_description' => 'Descubre las increíbles Islas Flotantes de los Uros en el Lago Titicaca. Tour de día completo desde Puno con guía profesional.',
            'short_description' => 'Visita las famosas Islas Flotantes de los Uros en el Lago Titicaca, conoce su cultura ancestral y disfruta de un día inolvidable.',
            'long_description' => '<h2>Descubre las Islas Flotantes de los Uros</h2>
<p>Embárcate en una aventura única visitando las legendarias Islas Flotantes de los Uros en el Lago Titicaca, el lago navegable más alto del mundo. Durante este tour de día completo, tendrás la oportunidad de conocer a los habitantes de estas islas artificiales construidas con totora, una planta acuática que crece en el lago.</p>

<h3>Una experiencia cultural inolvidable</h3>
<p>Los Uros son una cultura pre-incaica que ha mantenido sus tradiciones durante siglos. Aprenderás sobre su estilo de vida único, sus técnicas de construcción con totora, y podrás interactuar directamente con las familias locales. También tendrás la oportunidad de navegar en sus tradicionales balsas de totora.</p>

<h3>Paisajes espectaculares</h3>
<p>El Lago Titicaca ofrece vistas impresionantes con sus aguas azules cristalinas y el telón de fondo de los Andes. Es el escenario perfecto para fotografías memorables y una experiencia de conexión con la naturaleza.</p>',

            'itinerary' => '<h3>Itinerario del Tour</h3>
<ul>
<li><strong>07:00 AM:</strong> Recojo de su hotel en Puno</li>
<li><strong>07:30 AM:</strong> Salida hacia el puerto de Puno</li>
<li><strong>08:00 AM:</strong> Embarque y navegación hacia las Islas Flotantes</li>
<li><strong>08:40 AM:</strong> Llegada a las Islas Flotantes de los Uros</li>
<li><strong>08:45 AM - 10:30 AM:</strong> Visita guiada por las islas, explicación sobre la cultura Uros</li>
<li><strong>10:30 AM:</strong> Paseo opcional en balsa de totora (costo adicional)</li>
<li><strong>11:00 AM:</strong> Tiempo libre para compras de artesanías</li>
<li><strong>11:30 AM:</strong> Retorno al puerto de Puno</li>
<li><strong>12:30 PM:</strong> Llegada a Puno y traslado a su hotel</li>
</ul>',

            'what_includes' => '<ul>
<li>Transporte hotel - puerto - hotel</li>
<li>Lancha turística con asientos cómodos</li>
<li>Guía profesional bilingüe (Español/Inglés)</li>
<li>Entrada a las Islas Flotantes</li>
<li>Demostración cultural</li>
<li>Botiquín de primeros auxilios</li>
<li>Oxígeno de emergencia</li>
</ul>',

            'what_not_includes' => '<ul>
<li>Alimentación y bebidas</li>
<li>Paseo en balsa de totora (S/. 10 opcional)</li>
<li>Propinas</li>
<li>Compras de souvenirs</li>
<li>Gastos personales</li>
<li>Seguro de viaje</li>
</ul>',

            'recommendations' => '<h3>Recomendaciones importantes</h3>
<ul>
<li>Llevar protector solar y sombrero (el sol es muy fuerte en el lago)</li>
<li>Usar ropa abrigadora en capas (puede hacer frío por la mañana)</li>
<li>Llevar cámara fotográfica con batería extra</li>
<li>Hidratarse bien antes y durante el tour</li>
<li>Si sufre de mareos, tome medicamento preventivo</li>
<li>Llevar efectivo en soles para compras opcionales</li>
<li>Respetar las costumbres locales</li>
</ul>',

            'what_to_bring' => 'Documento de identidad, protector solar, gorra o sombrero, lentes de sol, cámara fotográfica, agua, snacks, dinero en efectivo',

            'policies' => '<h3>Políticas del Tour</h3>
<p>El tour opera todos los días del año, sujeto a condiciones climáticas. Mínimo 2 personas para operar. Los niños menores de 3 años no pagan. Se requiere documento de identidad para todos los participantes.</p>',

            'cancellation_policy' => '<h3>Política de Cancelación</h3>
<ul>
<li>Cancelación gratuita hasta 48 horas antes del tour</li>
<li>50% de cargo por cancelación entre 24-48 horas antes</li>
<li>100% de cargo por cancelación con menos de 24 horas</li>
<li>No show: 100% de cargo</li>
</ul>',
        ]);

        // Get or create nationalities
        $peruano = Nationality::firstOrCreate(['code' => 'PE'], ['name' => 'Peruano']);
        $extranjero = Nationality::firstOrCreate(['code' => 'EX'], ['name' => 'Extranjero']);

        // Get or create age stages
        $adulto = AgeStage::firstOrCreate(
            ['name' => 'Adulto'],
            ['min_age' => 12, 'max_age' => 65, 'order' => 2]
        );

        $nino = AgeStage::firstOrCreate(
            ['name' => 'Niño'],
            ['min_age' => 3, 'max_age' => 11, 'order' => 1]
        );

        $adultoMayor = AgeStage::firstOrCreate(
            ['name' => 'Adulto Mayor'],
            ['min_age' => 66, 'max_age' => 99, 'order' => 3]
        );

        // Add prices
        $prices = [
            // Adulto Peruano
            ['age_stage_id' => $adulto->id, 'nationality_id' => $peruano->id, 'amount' => 45],
            // Adulto Extranjero
            ['age_stage_id' => $adulto->id, 'nationality_id' => $extranjero->id, 'amount' => 60],
            // Niño Peruano
            ['age_stage_id' => $nino->id, 'nationality_id' => $peruano->id, 'amount' => 25],
            // Niño Extranjero
            ['age_stage_id' => $nino->id, 'nationality_id' => $extranjero->id, 'amount' => 35],
            // Adulto Mayor Peruano
            ['age_stage_id' => $adultoMayor->id, 'nationality_id' => $peruano->id, 'amount' => 40],
            // Adulto Mayor Extranjero
            ['age_stage_id' => $adultoMayor->id, 'nationality_id' => $extranjero->id, 'amount' => 55],
        ];

        foreach ($prices as $price) {
            TourPrice::create([
                'tour_id' => $tour->id,
                'age_stage_id' => $price['age_stage_id'],
                'nationality_id' => $price['nationality_id'],
                'amount' => $price['amount'],
                'min_participants' => 1,
                'max_participants' => 10,
                'active' => true,
            ]);
        }

        // Add media gallery
        $images = [
            [
                'path' => 'tours/gallery/uros-1.jpg',
                'alt_text' => 'Islas Flotantes de los Uros',
                'title_text' => 'Vista panorámica de las Islas Flotantes',
                'description' => 'Las coloridas islas flotantes construidas con totora en el Lago Titicaca',
                'order' => 1,
            ],
            [
                'path' => 'tours/gallery/uros-2.jpg',
                'alt_text' => 'Casa tradicional Uros',
                'title_text' => 'Arquitectura tradicional',
                'description' => 'Casa típica construida completamente con totora',
                'order' => 2,
            ],
            [
                'path' => 'tours/gallery/uros-3.jpg',
                'alt_text' => 'Balsa de totora',
                'title_text' => 'Embarcación tradicional',
                'description' => 'Balsa de totora decorada con cabezas de puma',
                'order' => 3,
            ],
            [
                'path' => 'tours/gallery/uros-4.jpg',
                'alt_text' => 'Familia Uros',
                'title_text' => 'Habitantes locales',
                'description' => 'Familia Uros con trajes tradicionales',
                'order' => 4,
            ],
            [
                'path' => 'tours/gallery/uros-5.jpg',
                'alt_text' => 'Lago Titicaca',
                'title_text' => 'Vista del lago',
                'description' => 'El majestuoso Lago Titicaca al atardecer',
                'order' => 5,
            ],
        ];

        foreach ($images as $image) {
            TourMediaGallery::create([
                'tour_id' => $tour->id,
                'path' => $image['path'],
                'alt_text' => $image['alt_text'],
                'title_text' => $image['title_text'],
                'description' => $image['description'],
                'order' => $image['order'],
            ]);
        }

        $this->command->info("Sample tour 'Tour al Lago Titicaca' created with ID: {$tour->id}");
    }
}