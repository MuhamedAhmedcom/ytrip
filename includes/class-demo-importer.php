<?php
/**
 * YTrip Demo Importer
 * 
 * One-click demo installation for CodeCanyon compliance.
 * Imports sample tours, destinations, categories, and settings.
 *
 * @package YTrip
 * @since 2.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YTrip_Demo_Importer
 */
class YTrip_Demo_Importer {

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Demo data.
     *
     * @var array
     */
    private $demo_data = [];

    /**
     * Import log.
     *
     * @var array
     */
    private $import_log = [];

    /**
     * Image IDs mapping.
     *
     * @var array
     */
    private $image_map = [];

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->demo_data = $this->get_demo_data();
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    private function init_hooks() {
        add_action('wp_ajax_ytrip_import_demo', [$this, 'ajax_import_demo']);
        add_action('wp_ajax_ytrip_remove_demo', [$this, 'ajax_remove_demo']);
    }

    /**
     * Get demo data.
     *
     * @return array
     */
    private function get_demo_data() {
        return [
            'destinations' => [
                [
                    'name' => 'Cairo',
                    'slug' => 'cairo',
                    'description' => 'Explore the ancient wonders of Cairo, home to the Great Pyramids of Giza, the Sphinx, and the Egyptian Museum.',
                    'image' => 'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=800',
                ],
                [
                    'name' => 'Luxor',
                    'slug' => 'luxor',
                    'description' => 'Discover the world\'s greatest open-air museum with the Valley of the Kings and Karnak Temple.',
                    'image' => 'https://images.unsplash.com/photo-1568322445389-f64ac2515020?w=800',
                ],
                [
                    'name' => 'Aswan',
                    'slug' => 'aswan',
                    'description' => 'Experience the beauty of the Nile in Aswan, featuring Philae Temple and Abu Simbel.',
                    'image' => 'https://images.unsplash.com/photo-1553913861-c0fddf2619ee?w=800',
                ],
                [
                    'name' => 'Hurghada',
                    'slug' => 'hurghada',
                    'description' => 'Enjoy pristine beaches and world-class diving in the Red Sea paradise.',
                    'image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800',
                ],
                [
                    'name' => 'Sharm El Sheikh',
                    'slug' => 'sharm-el-sheikh',
                    'description' => 'A premier resort destination with stunning coral reefs and desert adventures.',
                    'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800',
                ],
                [
                    'name' => 'Alexandria',
                    'slug' => 'alexandria',
                    'description' => 'The Mediterranean jewel: Qaitbay Citadel, Bibliotheca Alexandrina, and Roman ruins.',
                    'image' => 'https://images.unsplash.com/photo-1578894381163-e72c17f2d45f?w=800',
                ],
                [
                    'name' => 'Sinai',
                    'slug' => 'sinai',
                    'description' => 'Mount Sinai, St. Catherine\'s Monastery, and dramatic desert landscapes.',
                    'image' => 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=800',
                ],
                [
                    'name' => 'Fayoum',
                    'slug' => 'fayoum',
                    'description' => 'Oasis lakes, Wadi El Rayan waterfalls, and traditional pottery villages.',
                    'image' => 'https://images.unsplash.com/photo-1473580044384-7ba9967e16a0?w=800',
                ],
                [
                    'name' => 'Marsa Alam',
                    'slug' => 'marsa-alam',
                    'description' => 'Pristine Red Sea coast, dolphin encounters, and untouched reefs.',
                    'image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800',
                ],
            ],
            'categories' => [
                ['name' => 'Cultural Tours', 'slug' => 'cultural', 'description' => 'Explore ancient history and local culture'],
                ['name' => 'Adventure Tours', 'slug' => 'adventure', 'description' => 'Thrilling outdoor experiences'],
                ['name' => 'Beach & Relaxation', 'slug' => 'beach', 'description' => 'Sun, sand, and serenity'],
                ['name' => 'Nile Cruises', 'slug' => 'nile-cruise', 'description' => 'Sail the legendary Nile River'],
                ['name' => 'Desert Safari', 'slug' => 'desert-safari', 'description' => 'Explore the majestic Sahara'],
                ['name' => 'Diving & Snorkeling', 'slug' => 'diving', 'description' => 'Underwater Red Sea adventures'],
                ['name' => 'Historical', 'slug' => 'historical', 'description' => 'Monuments, museums, and heritage sites'],
                ['name' => 'Family Tours', 'slug' => 'family', 'description' => 'Kid-friendly activities and comfortable pacing'],
                ['name' => 'Luxury Experiences', 'slug' => 'luxury', 'description' => 'Premium accommodation and exclusive access'],
                ['name' => 'Photography Tours', 'slug' => 'photography', 'description' => 'Scenic spots and photo-focused itineraries'],
            ],
            'tours' => [
                [
                    'title' => 'Pyramids & Sphinx Day Tour',
                    'slug' => 'pyramids-sphinx-day-tour',
                    'content' => '<p>Experience the wonders of ancient Egypt on this comprehensive day tour of the Giza Plateau. Visit the Great Pyramid of Khufu, one of the Seven Wonders of the Ancient World, marvel at the enigmatic Sphinx, and explore the Valley Temple.</p><h3>Tour Highlights</h3><ul><li>Professional Egyptologist guide</li><li>Air-conditioned vehicle</li><li>Entrance fees included</li><li>Lunch at local restaurant</li></ul>',
                    'excerpt' => 'Discover the iconic Pyramids of Giza and the mysterious Sphinx on this unforgettable day tour.',
                    'destination' => 'cairo',
                    'categories' => ['cultural'],
                    'featured' => true,
                    'duration' => '8 hours',
                    'group_size' => '2-15',
                    'difficulty' => 'easy',
                    'image' => 'https://images.unsplash.com/photo-1503177119275-0aa32b3a9368?w=800',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1539650116574-8efeb43e2750?w=800',
                        'https://images.unsplash.com/photo-1568322445389-f64ac2515020?w=800',
                        'https://images.unsplash.com/photo-1503177119275-0aa32b3a9368?w=800',
                        'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=800',
                    ],
                    'hero_gallery_mode' => 'slider',
                    'price' => 89,
                    'sale_price' => 69,
                    'highlights' => [
                        'Great Pyramid of Khufu',
                        'The Great Sphinx',
                        'Valley Temple',
                        'Panoramic view point',
                    ],
                    'included' => [
                        'Hotel pickup and drop-off',
                        'Professional Egyptologist guide',
                        'Entrance fees',
                        'Lunch',
                        'Bottled water',
                    ],
                    'excluded' => [
                        'Tips and gratuities',
                        'Personal expenses',
                        'Optional activities',
                    ],
                ],
                [
                    'title' => 'Luxor East & West Bank Full Day',
                    'slug' => 'luxor-east-west-bank',
                    'content' => '<p>Discover the treasures of Luxor, the world\'s greatest open-air museum. This comprehensive tour covers both banks of the Nile, from the Valley of the Kings to Karnak Temple.</p><h3>What You\'ll See</h3><ul><li>Valley of the Kings - Tombs of pharaohs</li><li>Temple of Hatshepsut - Architectural masterpiece</li><li>Colossi of Memnon - Ancient statues</li><li>Karnak Temple - Largest temple complex</li></ul>',
                    'excerpt' => 'Explore both banks of the Nile in Luxor, visiting the Valley of the Kings, Hatshepsut Temple, and Karnak.',
                    'destination' => 'luxor',
                    'categories' => ['cultural'],
                    'featured' => true,
                    'duration' => '10 hours',
                    'group_size' => '2-12',
                    'difficulty' => 'moderate',
                    'image' => 'https://images.unsplash.com/photo-1568322445389-f64ac2515020?w=800',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1539650116574-8efeb43e2750?w=800',
                        'https://images.unsplash.com/photo-1568322445389-f64ac2515020?w=800',
                        'https://images.unsplash.com/photo-1553913861-c0fddf2619ee?w=800',
                        'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=800',
                        'https://images.unsplash.com/photo-1473580044384-7ba9967e16a0?w=800',
                    ],
                    'hero_gallery_mode' => 'carousel',
                    'price' => 125,
                    'highlights' => [
                        'Valley of the Kings (3 tombs)',
                        'Temple of Queen Hatshepsut',
                        'Colossi of Memnon',
                        'Karnak Temple Complex',
                    ],
                    'included' => [
                        'Hotel pickup and drop-off',
                        'Professional guide',
                        'Entrance fees',
                        'Lunch',
                    ],
                    'excluded' => [
                        'Tips',
                        'Drinks',
                    ],
                ],
                [
                    'title' => 'Red Sea Diving Adventure',
                    'slug' => 'red-sea-diving-adventure',
                    'content' => '<p>Experience the underwater paradise of the Red Sea. Crystal clear waters, vibrant coral reefs, and abundant marine life make this a must-do adventure.</p><h3>What to Expect</h3><ul><li>Two guided dives</li><li>All equipment provided</li><li>Beginner-friendly options</li><li>Stunning coral formations</li></ul>',
                    'excerpt' => 'Dive into the crystal-clear waters of the Red Sea and discover a vibrant underwater world.',
                    'destination' => 'hurghada',
                    'categories' => ['adventure', 'diving'],
                    'featured' => true,
                    'duration' => '6 hours',
                    'group_size' => '1-8',
                    'difficulty' => 'easy',
                    'image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800',
                    'gallery' => [],
                    'hero_gallery_mode' => 'single_image',
                    'price' => 75,
                    'highlights' => [
                        'Two guided dives',
                        'Professional PADI instructors',
                        'All equipment included',
                        'Underwater photography available',
                    ],
                    'included' => [
                        'Diving equipment',
                        'Professional instructor',
                        'Boat trip',
                        'Lunch on board',
                    ],
                    'excluded' => [
                        'Underwater photos',
                        'Tips',
                    ],
                ],
                [
                    'title' => 'Desert Safari & Bedouin Dinner',
                    'slug' => 'desert-safari-bedouin-dinner',
                    'content' => '<p>Experience the magic of the Egyptian desert. Drive through stunning landscapes, visit a traditional Bedouin village, and enjoy a delicious dinner under the stars.</p>',
                    'excerpt' => 'An unforgettable desert adventure with quad biking, camel ride, and traditional Bedouin dinner.',
                    'destination' => 'sharm-el-sheikh',
                    'categories' => ['adventure', 'desert-safari'],
                    'featured' => false,
                    'duration' => '5 hours',
                    'group_size' => '4-20',
                    'difficulty' => 'easy',
                    'image' => 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=800',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=800',
                        'https://images.unsplash.com/photo-1473580044384-7ba9967e16a0?w=800',
                        'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800',
                    ],
                    'hero_gallery_mode' => 'slider',
                    'price' => 55,
                    'highlights' => [
                        'Quad bike adventure',
                        'Camel ride',
                        'Bedouin village visit',
                        'Traditional dinner',
                        'Stargazing',
                    ],
                    'included' => [
                        'Hotel transfers',
                        'Quad bike',
                        'Camel ride',
                        'Dinner',
                        'Soft drinks',
                    ],
                    'excluded' => [
                        'Tips',
                        'Personal expenses',
                    ],
                ],
                [
                    'title' => 'Nile Cruise Luxor to Aswan',
                    'slug' => 'nile-cruise-luxor-aswan',
                    'content' => '<p>Sail the legendary Nile River on this 4-day cruise from Luxor to Aswan. Visit ancient temples, enjoy deluxe accommodation, and experience the magic of Egypt from the water.</p>',
                    'excerpt' => 'A luxurious 4-day Nile cruise visiting Edfu, Kom Ombo, and Aswan with all meals included.',
                    'destination' => 'luxor',
                    'categories' => ['nile-cruise', 'cultural'],
                    'featured' => true,
                    'duration' => '4 days',
                    'group_size' => '2-100',
                    'difficulty' => 'easy',
                    'image' => 'https://images.unsplash.com/photo-1553913861-c0fddf2619ee?w=800',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1553913861-c0fddf2619ee?w=800',
                        'https://images.unsplash.com/photo-1568322445389-f64ac2515020?w=800',
                        'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=800',
                        'https://images.unsplash.com/photo-1539650116574-8efeb43e2750?w=800',
                        'https://images.unsplash.com/photo-1503177119275-0aa32b3a9368?w=800',
                    ],
                    'hero_gallery_mode' => 'carousel',
                    'price' => 450,
                    'sale_price' => 399,
                    'highlights' => [
                        'Deluxe cabin with Nile view',
                        'All meals included',
                        'Edfu & Kom Ombo temples',
                        'Professional guides',
                        'Entertainment shows',
                    ],
                    'included' => [
                        '4 nights accommodation',
                        'All meals',
                        'Guided excursions',
                        'Entrance fees',
                    ],
                    'excluded' => [
                        'Tips',
                        'Drinks',
                        'Optional tours',
                    ],
                ],
                [
                    'title' => 'Abu Simbel Day Trip',
                    'slug' => 'abu-simbel-day-trip',
                    'content' => '<p>Visit the magnificent Abu Simbel temples, carved into the mountainside by Ramses II. These UNESCO World Heritage sites are among Egypt\'s most impressive monuments.</p>',
                    'excerpt' => 'Marvel at the colossal temples of Abu Simbel, a testament to ancient Egyptian engineering.',
                    'destination' => 'aswan',
                    'categories' => ['cultural'],
                    'featured' => false,
                    'duration' => '8 hours',
                    'group_size' => '2-15',
                    'difficulty' => 'easy',
                    'image' => 'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=800',
                    'gallery' => [],
                    'hero_gallery_mode' => 'single_image',
                    'price' => 95,
                    'highlights' => [
                        'Great Temple of Ramses II',
                        'Temple of Hathor',
                        'Lake Nasser views',
                        'Expert Egyptologist guide',
                    ],
                    'included' => [
                        'Hotel pickup/drop-off',
                        'Air-conditioned transport',
                        'Entrance fees',
                        'Guide',
                    ],
                    'excluded' => [
                        'Lunch',
                        'Tips',
                    ],
                ],
                [
                    'title' => 'Alexandria Day Tour: Citadel & Library',
                    'slug' => 'alexandria-citadel-library-tour',
                    'content' => '<p>Discover the Mediterranean coast on a full-day tour of Alexandria. Visit the iconic Qaitbay Citadel, the modern Bibliotheca Alexandrina, the Roman Amphitheatre, and the Catacombs of Kom El Shoqafa. Enjoy a seafood lunch by the corniche.</p><h3>Itinerary</h3><ul><li>Qaitbay Citadel – 15th-century fortress</li><li>Bibliotheca Alexandrina – iconic library</li><li>Roman Amphitheatre & Pompey\'s Pillar</li><li>Catacombs of Kom El Shoqafa</li><li>Corniche & optional lunch</li></ul>',
                    'excerpt' => 'Full-day exploration of Alexandria: Qaitbay Citadel, Bibliotheca Alexandrina, Roman ruins, and the Mediterranean corniche.',
                    'destination' => 'alexandria',
                    'categories' => ['cultural', 'historical'],
                    'featured' => true,
                    'duration' => '10 hours',
                    'group_size' => '2-12',
                    'difficulty' => 'easy',
                    'image' => 'https://images.unsplash.com/photo-1578894381163-e72c17f2d45f?w=800',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1578894381163-e72c17f2d45f?w=800',
                        'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=800',
                        'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=800',
                        'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800',
                        'https://images.unsplash.com/photo-1473580044384-7ba9967e16a0?w=800',
                    ],
                    'hero_gallery_mode' => 'carousel',
                    'price' => 79,
                    'sale_price' => 65,
                    'highlights' => [
                        'Qaitbay Citadel',
                        'Bibliotheca Alexandrina',
                        'Roman Amphitheatre',
                        'Catacombs of Kom El Shoqafa',
                        'Corniche views',
                    ],
                    'included' => [
                        'Hotel pickup and drop-off from Cairo',
                        'Air-conditioned vehicle',
                        'Professional guide',
                        'Entrance fees',
                        'Lunch at local restaurant',
                    ],
                    'excluded' => [
                        'Tips and gratuities',
                        'Personal expenses',
                    ],
                ],
                [
                    'title' => 'Mount Sinai Sunrise & St. Catherine\'s Monastery',
                    'slug' => 'mount-sinai-sunrise-st-catherine',
                    'content' => '<p>Climb Mount Sinai before dawn and watch the sunrise from the summit where Moses received the Ten Commandments. Descend to visit the UNESCO-listed St. Catherine\'s Monastery, one of the world\'s oldest working Christian monasteries, and see the Burning Bush.</p><h3>Experience</h3><ul><li>Night climb with Bedouin guide</li><li>Sunrise from 2,285m summit</li><li>St. Catherine\'s Monastery & library</li><li>Burning Bush and icon collection</li></ul>',
                    'excerpt' => 'Sunrise climb of Mount Sinai and visit to St. Catherine\'s Monastery with a Bedouin guide.',
                    'destination' => 'sinai',
                    'categories' => ['adventure', 'historical'],
                    'featured' => true,
                    'duration' => '8 hours',
                    'group_size' => '2-15',
                    'difficulty' => 'moderate',
                    'image' => 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=800',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=800',
                        'https://images.unsplash.com/photo-1473580044384-7ba9967e16a0?w=800',
                        'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=800',
                        'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800',
                    ],
                    'hero_gallery_mode' => 'slider',
                    'price' => 85,
                    'highlights' => [
                        'Sunrise from Mount Sinai summit',
                        'St. Catherine\'s Monastery',
                        'Burning Bush site',
                        'Bedouin guide',
                    ],
                    'included' => [
                        'Hotel pickup/drop-off (Sharm or Dahab)',
                        'Bedouin guide for climb',
                        'Entrance to monastery',
                        'Bottled water',
                    ],
                    'excluded' => [
                        'Camel for ascent (optional)',
                        'Tips',
                        'Breakfast',
                    ],
                ],
                [
                    'title' => 'Fayoum Oasis: Wadi El Rayan & Magic Lake',
                    'slug' => 'fayoum-wadi-el-rayan-magic-lake',
                    'content' => '<p>Escape to the Fayoum Oasis for a day of natural beauty. Visit Wadi El Rayan’s waterfalls and lakes, the Magic Lake with its changing colours, and the traditional pottery village of Tunis. Perfect for photography and nature lovers.</p><h3>Highlights</h3><ul><li>Wadi El Rayan protected area</li><li>Upper and Lower lakes</li><li>Magic Lake viewpoint</li><li>Tunis village pottery</li></ul>',
                    'excerpt' => 'Day trip to Fayoum Oasis: Wadi El Rayan waterfalls, Magic Lake, and Tunis pottery village.',
                    'destination' => 'fayoum',
                    'categories' => ['adventure', 'photography', 'family'],
                    'featured' => false,
                    'duration' => '10 hours',
                    'group_size' => '2-10',
                    'difficulty' => 'easy',
                    'image' => 'https://images.unsplash.com/photo-1473580044384-7ba9967e16a0?w=800',
                    'gallery' => [],
                    'hero_gallery_mode' => 'single_image',
                    'price' => 65,
                    'highlights' => [
                        'Wadi El Rayan lakes and waterfalls',
                        'Magic Lake',
                        'Tunis pottery village',
                        'Desert and oasis scenery',
                    ],
                    'included' => [
                        'Cairo pickup and drop-off',
                        '4x4 or minibus transport',
                        'Entrance fees',
                        'Local guide',
                    ],
                    'excluded' => [
                        'Lunch',
                        'Tips',
                    ],
                ],
                [
                    'title' => 'Marsa Alam Dolphin House Snorkeling',
                    'slug' => 'marsa-alam-dolphin-house-snorkeling',
                    'content' => '<p>Snorkel in the famous Dolphin House near Marsa Alam, where spinner dolphins are often seen in their natural habitat. Crystal-clear waters, vibrant reefs, and the chance to swim near these friendly mammals make this a memorable half-day trip.</p><h3>What to Expect</h3><ul><li>Boat trip to Dolphin House reef</li><li>Snorkeling with optional dolphin encounter</li><li>Pristine coral and fish</li><li>All equipment provided</li></ul>',
                    'excerpt' => 'Snorkeling at Marsa Alam\'s Dolphin House with a chance to see spinner dolphins and pristine reefs.',
                    'destination' => 'marsa-alam',
                    'categories' => ['diving', 'beach', 'family'],
                    'featured' => true,
                    'duration' => '5 hours',
                    'group_size' => '2-12',
                    'difficulty' => 'easy',
                    'image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800',
                        'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800',
                        'https://images.unsplash.com/photo-1682687220742-aba13b6e50ba?w=800',
                    ],
                    'hero_gallery_mode' => 'slider',
                    'price' => 55,
                    'sale_price' => 45,
                    'highlights' => [
                        'Dolphin House reef',
                        'Spinner dolphins (in the wild)',
                        'Snorkeling equipment',
                        'Small group experience',
                    ],
                    'included' => [
                        'Hotel pickup (Marsa Alam area)',
                        'Boat trip',
                        'Snorkeling gear',
                        'Guide',
                    ],
                    'excluded' => [
                        'Underwater photos',
                        'Tips',
                    ],
                ],
                [
                    'title' => 'Luxor Hot Air Balloon at Sunrise',
                    'slug' => 'luxor-hot-air-balloon-sunrise',
                    'content' => '<p>Soar over the temples and tombs of Luxor in a hot air balloon at sunrise. Enjoy panoramic views of the Valley of the Kings, Hatshepsut Temple, the Colossi of Memnon, and the Nile as the sun rises over the Theban hills.</p><h3>Experience</h3><ul><li>Early morning launch</li><li>45–60 minute flight</li><li>Views of West Bank monuments</li><li>Champagne and certificate</li></ul>',
                    'excerpt' => 'Sunrise hot air balloon flight over Luxor\'s West Bank with views of temples and the Nile.',
                    'destination' => 'luxor',
                    'categories' => ['adventure', 'luxury', 'photography'],
                    'featured' => true,
                    'duration' => '3 hours',
                    'group_size' => '2-16',
                    'difficulty' => 'easy',
                    'image' => 'https://images.unsplash.com/photo-1553913861-c0fddf2619ee?w=800',
                    'gallery' => [
                        'https://images.unsplash.com/photo-1553913861-c0fddf2619ee?w=800',
                        'https://images.unsplash.com/photo-1568322445389-f64ac2515020?w=800',
                        'https://images.unsplash.com/photo-1503177119275-0aa32b3a9368?w=800',
                        'https://images.unsplash.com/photo-1539650116574-8efeb43e2750?w=800',
                        'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=800',
                    ],
                    'hero_gallery_mode' => 'carousel',
                    'price' => 165,
                    'highlights' => [
                        'Sunrise balloon flight',
                        'Valley of the Kings from above',
                        'Nile and Theban hills views',
                        'Certificate and champagne',
                    ],
                    'included' => [
                        'Hotel pickup and drop-off',
                        'Balloon flight (45–60 min)',
                        'Champagne toast',
                        'Flight certificate',
                    ],
                    'excluded' => [
                        'Tips',
                        'Personal items',
                    ],
                ],
                [
                    'title' => 'Cairo Museum & Old Cairo Churches',
                    'slug' => 'cairo-museum-old-cairo-churches',
                    'content' => '<p>Explore the Egyptian Museum in Tahrir (or the new Grand Egyptian Museum when open) and the historic churches and synagogues of Old Cairo. See the Hanging Church, St. Sergius, Ben Ezra Synagogue, and the Coptic Museum.</p><h3>Highlights</h3><ul><li>Egyptian Museum treasures</li><li>Hanging Church (El Muallaqa)</li><li>St. Sergius Church</li><li>Ben Ezra Synagogue & Coptic Museum</li></ul>',
                    'excerpt' => 'Half-day tour of the Egyptian Museum and Old Cairo\'s churches and Coptic heritage.',
                    'destination' => 'cairo',
                    'categories' => ['cultural', 'historical'],
                    'featured' => false,
                    'duration' => '6 hours',
                    'group_size' => '2-12',
                    'difficulty' => 'easy',
                    'image' => 'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=800',
                    'gallery' => [],
                    'hero_gallery_mode' => 'single_image',
                    'price' => 59,
                    'highlights' => [
                        'Egyptian Museum (or GEM)',
                        'Hanging Church',
                        'St. Sergius & Ben Ezra',
                        'Coptic Museum',
                    ],
                    'included' => [
                        'Hotel pickup and drop-off',
                        'Expert guide',
                        'Entrance fees',
                        'Bottled water',
                    ],
                    'excluded' => [
                        'Lunch',
                        'Tips',
                    ],
                ],
            ],
            'testimonials' => [
                [
                    'name' => 'Sarah Johnson',
                    'role' => 'Traveler from USA',
                    'content' => 'An incredible experience! The Pyramids tour was everything I imagined and more. Our guide was knowledgeable and the organization was perfect.',
                    'rating' => 5,
                ],
                [
                    'name' => 'Michael Brown',
                    'role' => 'Adventure Seeker',
                    'content' => 'The Red Sea diving was absolutely stunning. Crystal clear waters and amazing coral reefs. Highly recommend!',
                    'rating' => 5,
                ],
                [
                    'name' => 'Emma Wilson',
                    'role' => 'Family Traveler',
                    'content' => 'Perfect family vacation! The kids loved the desert safari and we all enjoyed the Nile cruise. Thank you YTrip!',
                    'rating' => 5,
                ],
            ],
            'settings' => [
                'color_preset' => 'egyptian_gold',
                'homepage_layout' => 'modern',
                'currency' => 'USD',
                'show_ratings' => true,
            ],
        ];
    }

    /**
     * AJAX import demo.
     *
     * @return void
     */
    public function ajax_import_demo() {
        check_ajax_referer('ytrip_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ytrip')]);
        }

        $this->import_log = [];

        try {
            // Import in order: destinations, categories, images, tours, settings
            $this->import_destinations();
            $this->import_categories();
            $this->import_tours();
            $this->import_testimonials();
            $this->import_settings();

            wp_send_json_success([
                'message' => __('Demo content imported successfully!', 'ytrip'),
                'log' => $this->import_log,
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'log' => $this->import_log,
            ]);
        }
    }

    /**
     * Import destinations.
     *
     * @return void
     */
    private function import_destinations() {
        // Get custom taxonomy slug from settings
        $settings = get_option('ytrip_settings', []);
        $destination_slug = $settings['slug_destination'] ?? 'ytrip_destination';

        foreach ($this->demo_data['destinations'] as $dest) {
            // Check if exists
            $existing = get_term_by('slug', $dest['slug'], $destination_slug);

            if ($existing) {
                $this->import_log['destinations'][] = sprintf(
                    /* translators: %s: destination name */
                    __('Updated: %s', 'ytrip'),
                    $dest['name']
                );
                wp_update_term($existing->term_id, $destination_slug, [
                    'description' => $dest['description'],
                ]);
                $term_id = $existing->term_id;
            } else {
                $result = wp_insert_term($dest['name'], $destination_slug, [
                    'slug' => $dest['slug'],
                    'description' => $dest['description'],
                ]);

                if (is_wp_error($result)) {
                    $this->import_log['errors'][] = sprintf(
                        /* translators: %s: destination name */
                        __('Failed to create destination: %s', 'ytrip'),
                        $dest['name']
                    );
                    continue;
                }

                $term_id = $result['term_id'];
                $this->import_log['destinations'][] = sprintf(
                    /* translators: %s: destination name */
                    __('Created: %s', 'ytrip'),
                    $dest['name']
                );
            }

            // Import image
            if (!empty($dest['image'])) {
                $image_id = $this->import_image($dest['image'], $dest['name']);
                if ($image_id) {
                    update_term_meta($term_id, 'ytrip_destination_image', $image_id);
                }
            }
        }
    }

    /**
     * Import categories.
     *
     * @return void
     */
    private function import_categories() {
        // Get custom taxonomy slug from settings
        $settings = get_option('ytrip_settings', []);
        $category_slug = $settings['slug_category'] ?? 'ytrip_category';

        foreach ($this->demo_data['categories'] as $cat) {
            $existing = get_term_by('slug', $cat['slug'], $category_slug);

            if ($existing) {
                $this->import_log['categories'][] = sprintf(
                    /* translators: %s: category name */
                    __('Updated: %s', 'ytrip'),
                    $cat['name']
                );
                wp_update_term($existing->term_id, $category_slug, [
                    'description' => $cat['description'],
                ]);
            } else {
                $result = wp_insert_term($cat['name'], $category_slug, [
                    'slug' => $cat['slug'],
                    'description' => $cat['description'],
                ]);

                if (is_wp_error($result)) {
                    $this->import_log['errors'][] = sprintf(
                        /* translators: %s: category name */
                        __('Failed to create category: %s', 'ytrip'),
                        $cat['name']
                    );
                    continue;
                }

                $this->import_log['categories'][] = sprintf(
                    /* translators: %s: category name */
                    __('Created: %s', 'ytrip'),
                    $cat['name']
                );
            }
        }
    }

    /**
     * Import tours.
     *
     * @return void
     */
    private function import_tours() {
        // Get custom post type slug from settings
        $settings = get_option('ytrip_settings', []);
        $tour_slug = $settings['slug_tour'] ?? 'ytrip_tour';
        $destination_slug = $settings['slug_destination'] ?? 'ytrip_destination';
        $category_slug = $settings['slug_category'] ?? 'ytrip_category';

        foreach ($this->demo_data['tours'] as $tour) {
            // Check if exists
            $existing = get_page_by_path($tour['slug'], OBJECT, $tour_slug);

            if ($existing) {
                $this->import_log['tours'][] = sprintf(
                    /* translators: %s: tour title */
                    __('Updated: %s', 'ytrip'),
                    $tour['title']
                );
                $post_id = $existing->ID;

                wp_update_post([
                    'ID' => $post_id,
                    'post_title' => $tour['title'],
                    'post_content' => $tour['content'],
                    'post_excerpt' => $tour['excerpt'],
                ]);
            } else {
                $post_id = wp_insert_post([
                    'post_type' => $tour_slug,
                    'post_title' => $tour['title'],
                    'post_content' => $tour['content'],
                    'post_excerpt' => $tour['excerpt'],
                    'post_name' => $tour['slug'],
                    'post_status' => 'publish',
                ]);

                if (is_wp_error($post_id)) {
                    $this->import_log['errors'][] = sprintf(
                        /* translators: %s: tour title */
                        __('Failed to create tour: %s', 'ytrip'),
                        $tour['title']
                    );
                    continue;
                }

                $this->import_log['tours'][] = sprintf(
                    /* translators: %s: tour title */
                    __('Created: %s', 'ytrip'),
                    $tour['title']
                );
            }

            // Set destination
            if (!empty($tour['destination'])) {
                $dest_term = get_term_by('slug', $tour['destination'], $destination_slug);
                if ($dest_term) {
                    wp_set_post_terms($post_id, [$dest_term->term_id], $destination_slug);
                }
            }

            // Set categories
            if (!empty($tour['categories'])) {
                $cat_ids = [];
                foreach ($tour['categories'] as $cat_slug) {
                    $cat_term = get_term_by('slug', $cat_slug, $category_slug);
                    if ($cat_term) {
                        $cat_ids[] = $cat_term->term_id;
                    }
                }
                wp_set_post_terms($post_id, $cat_ids, $category_slug);
            }

            // Import featured image
            if (!empty($tour['image'])) {
                $image_id = $this->import_image($tour['image'], $tour['title']);
                if ($image_id) {
                    set_post_thumbnail($post_id, $image_id);
                }
            }

            // Import gallery
            $gallery_ids = [];
            if (!empty($tour['gallery'])) {
                foreach ($tour['gallery'] as $gallery_image) {
                    $gallery_id = $this->import_image($gallery_image, $tour['title'] . ' gallery');
                    if ($gallery_id) {
                        $gallery_ids[] = $gallery_id;
                    }
                }
            }

            $gallery_str = implode(',', $gallery_ids);
            $hero_mode = $this->normalize_hero_gallery_mode($tour, $gallery_ids);

            // Normalize group_size to fieldset format (min/max) when string "x-y"
            $group_size = $this->normalize_group_size($tour['group_size'] ?? '');

            // Normalize duration to tour_duration fieldset (days, nights, hours)
            $tour_duration = $this->normalize_tour_duration($tour['duration'] ?? '');

            // Normalize highlights to repeater format [['highlight' => '...'], ...]
            $highlights = $this->normalize_highlights($tour['highlights'] ?? []);

            // Normalize included/excluded to repeater format [['item' => '...', 'icon' => 'check'], ...]
            $included = $this->normalize_included_excluded($tour['included'] ?? [], true);
            $excluded = $this->normalize_included_excluded($tour['excluded'] ?? [], false);

            // Build full tour meta: core + all other features (itinerary, location, availability, FAQ, additional)
            $tour_meta = [
                'featured'          => $tour['featured'] ?? false,
                'duration'          => $tour['duration'] ?? '',
                'tour_duration'     => $tour_duration,
                'group_size'        => $group_size,
                'difficulty'        => $tour['difficulty'] ?? 'moderate',
                'highlights'        => $highlights,
                'included'          => $included,
                'excluded'          => $excluded,
                'gallery'           => $gallery_str,
                'tour_gallery'      => $gallery_str,
                'hero_gallery_mode' => $hero_mode,
                'pricing'           => [
                    'regular_price' => isset($tour['price']) ? (float) $tour['price'] : 0,
                    'sale_price'   => isset($tour['sale_price']) ? (float) $tour['sale_price'] : '',
                    'price_type'   => 'per_person',
                ],
            ];

            $extra = $this->build_extra_tour_meta($tour, $post_id);
            $tour_meta = array_merge($tour_meta, $extra);

            update_post_meta($post_id, 'ytrip_tour_details', $tour_meta);

            // Create WooCommerce product
            $this->create_woocommerce_product($post_id, $tour);
        }
    }

    /**
     * Build extra tour meta: Basic Info (tour_code, short_description, tour_type, languages),
     * Itinerary, Location, Pricing/Booking, Availability, FAQ, Related Tours, Additional Info.
     *
     * @param array $tour   Tour data from demo.
     * @param int   $post_id Tour post ID (for context).
     * @return array Meta key-value pairs to merge into ytrip_tour_details.
     */
    private function build_extra_tour_meta(array $tour, int $post_id): array {
        $dest_slugs = [
            'cairo'           => ['meeting' => 'Giza Plateau Visitor Center', 'time' => '08:00 AM', 'lat' => '29.9792', 'lng' => '31.1342', 'zoom' => '14'],
            'luxor'           => ['meeting' => 'Luxor Temple East Bank', 'time' => '06:00 AM', 'lat' => '25.6998', 'lng' => '32.6392', 'zoom' => '14'],
            'aswan'           => ['meeting' => 'Aswan Corniche', 'time' => '07:00 AM', 'lat' => '24.0889', 'lng' => '32.8998', 'zoom' => '14'],
            'hurghada'        => ['meeting' => 'Marina Hurghada', 'time' => '07:30 AM', 'lat' => '27.2579', 'lng' => '33.8116', 'zoom' => '14'],
            'sharm-el-sheikh' => ['meeting' => 'Hotel pickup (Sharm El Sheikh)', 'time' => '04:00 AM', 'lat' => '27.9158', 'lng' => '34.3300', 'zoom' => '12'],
            'alexandria'      => ['meeting' => 'Cairo hotel pickup or Alexandria Corniche', 'time' => '07:00 AM', 'lat' => '31.2001', 'lng' => '29.9187', 'zoom' => '14'],
            'sinai'           => ['meeting' => 'St. Catherine Village', 'time' => '01:00 AM', 'lat' => '28.5563', 'lng' => '33.9760', 'zoom' => '12'],
            'fayoum'          => ['meeting' => 'Cairo hotel pickup', 'time' => '06:00 AM', 'lat' => '29.3084', 'lng' => '30.8441', 'zoom' => '11'],
            'marsa-alam'      => ['meeting' => 'Marsa Alam Marina', 'time' => '08:00 AM', 'lat' => '25.0691', 'lng' => '34.8952', 'zoom' => '12'],
        ];
        $dest = $tour['destination'] ?? 'cairo';
        $loc = $dest_slugs[ $dest ] ?? $dest_slugs['cairo'];

        $short_desc = ! empty( $tour['excerpt'] ) ? wp_trim_words( wp_strip_all_tags( $tour['excerpt'] ), 25 ) : wp_trim_words( wp_strip_all_tags( $tour['content'] ?? '' ), 25 );
        $tour_code  = 'TR-' . str_pad( (string) ( $post_id % 1000 ), 3, '0', STR_PAD_LEFT );

        $duration_str = $tour['duration'] ?? '';
        $has_days     = (bool) preg_match( '/(\d+)\s*days?/i', $duration_str );
        $itinerary    = $this->build_default_itinerary( $tour, $has_days );

        $faq = [
            [
                'question' => __( 'What should I bring?', 'ytrip' ),
                'answer'   => __( 'Comfortable shoes, sunscreen, hat, and a refillable water bottle. For desert or diving tours we will send a specific list.', 'ytrip' ),
            ],
            [
                'question' => __( 'Is pickup included?', 'ytrip' ),
                'answer'   => __( 'Yes. We offer hotel pickup and drop-off within the tour area. Details are in the meeting point section.', 'ytrip' ),
            ],
            [
                'question' => __( 'What is your cancellation policy?', 'ytrip' ),
                'answer'   => __( 'Free cancellation up to 24 hours before the start time. Later cancellations may incur a fee as per our policy.', 'ytrip' ),
            ],
        ];

        $start_times = [
            ['time' => $loc['time']],
            ['time' => '09:00 AM'],
        ];

        $things_to_bring = __( 'Comfortable walking shoes, sunscreen, hat, sunglasses, refillable water bottle, camera. For water activities: swimwear and towel.', 'ytrip' );
        $know_before     = '<p>' . __( 'Please confirm your pickup location and time the day before. Some sites have dress codes (e.g. shoulders and knees covered). Bring local currency for tips and small purchases.', 'ytrip' ) . '</p>';
        $cancellation    = '<p>' . __( 'Free cancellation up to 24 hours before the experience start time for a full refund. No refund for no-shows or cancellations within 24 hours.', 'ytrip' ) . '</p>';

        $custom_fields = [
            ['label' => __( 'Duration', 'ytrip' ), 'value' => $duration_str, 'icon' => ''],
            ['label' => __( 'Group size', 'ytrip' ), 'value' => is_array( $tour['group_size'] ?? null ) ? ( ( $tour['group_size']['min'] ?? '' ) . '–' . ( $tour['group_size']['max'] ?? '' ) ) : ( $tour['group_size'] ?? '' ), 'icon' => ''],
        ];

        return [
            'tour_code'           => $tour_code,
            'short_description'   => $short_desc,
            'tour_type'           => 'group',
            'languages'           => ['en', 'ar'],
            'itinerary'           => $itinerary,
            'meeting_point'       => $loc['meeting'],
            'meeting_time'        => $loc['time'],
            'end_point'           => __( 'Same as meeting point (or as specified in itinerary)', 'ytrip' ),
            'map_location'        => [
                'address'   => $loc['meeting'],
                'latitude'  => $loc['lat'],
                'longitude' => $loc['lng'],
                'zoom'      => $loc['zoom'],
            ],
            'booking_method'       => 'woocommerce',
            'availability_type'    => 'always',
            'start_times'         => $start_times,
            'max_bookings_per_day' => 20,
            'faq'                 => $faq,
            'related_mode'        => 'auto',
            'related_taxonomy'    => 'ytrip_destination',
            'related_count'       => 4,
            'things_to_bring'    => $things_to_bring,
            'know_before_you_go'  => $know_before,
            'cancellation_policy'  => $cancellation,
            'custom_fields'      => $custom_fields,
        ];
    }

    /**
     * Build default itinerary (1 or 2 days) for demo tours.
     *
     * @param array $tour      Tour data.
     * @param bool  $multi_day Whether to add a second day.
     * @return array Itinerary group items.
     */
    private function build_default_itinerary(array $tour, bool $multi_day): array {
        $day1_title = isset( $tour['title'] ) ? sprintf( __( 'Day 1: %s', 'ytrip' ), wp_trim_words( $tour['title'], 5 ) ) : __( 'Day 1: Main program', 'ytrip' );
        $day1_desc  = ! empty( $tour['content'] ) ? wp_kses_post( wp_trim_words( wp_strip_all_tags( $tour['content'] ), 50 ) ) : '<p>' . __( 'Full program as described in the tour overview. Your guide will meet you at the meeting point and take you through the experience.', 'ytrip' ) . '</p>';
        $itinerary  = [
            [
                'day_number'      => 1,
                'day_title'       => $day1_title,
                'day_description' => $day1_desc,
                'day_image'       => '',
                'meals'           => ['breakfast', 'lunch'],
                'accommodation'   => '',
                'activities'      => [
                    ['time' => '08:00', 'activity' => __( 'Meet at meeting point', 'ytrip' )],
                    ['time' => '12:00', 'activity' => __( 'Main activities / sightseeing', 'ytrip' )],
                ],
            ],
        ];
        if ( $multi_day ) {
            $itinerary[] = [
                'day_number'      => 2,
                'day_title'       => __( 'Day 2: Continuation', 'ytrip' ),
                'day_description' => '<p>' . __( 'Second day of the program. Details will be confirmed with your guide. Meals and accommodation as per itinerary.', 'ytrip' ) . '</p>',
                'day_image'       => '',
                'meals'           => ['breakfast', 'lunch', 'dinner'],
                'accommodation'   => __( 'Included (as per tour type)', 'ytrip' ),
                'activities'      => [],
            ];
        }
        return $itinerary;
    }

    /**
     * Normalize hero gallery mode: use tour value or derive from gallery count.
     *
     * @param array $tour        Tour data.
     * @param array $gallery_ids Gallery attachment IDs.
     * @return string 'single_image'|'slider'|'carousel'
     */
    private function normalize_hero_gallery_mode(array $tour, array $gallery_ids): string {
        if (isset($tour['hero_gallery_mode']) && in_array($tour['hero_gallery_mode'], ['single_image', 'slider', 'carousel'], true)) {
            return $tour['hero_gallery_mode'];
        }
        if (count($gallery_ids) <= 1) {
            return 'single_image';
        }
        return 'slider';
    }

    /**
     * Normalize group_size to fieldset format.
     *
     * @param mixed $group_size String "min-max" or already array.
     * @return array{min?: int, max?: int}|string
     */
    private function normalize_group_size($group_size) {
        if (is_array($group_size) && (isset($group_size['min']) || isset($group_size['max']))) {
            return $group_size;
        }
        if (is_string($group_size) && preg_match('/^(\d+)\s*[-–]\s*(\d+)$/', trim($group_size), $m)) {
            return ['min' => (int) $m[1], 'max' => (int) $m[2]];
        }
        return $group_size;
    }

    /**
     * Normalize duration string to tour_duration fieldset (days, nights, hours).
     *
     * @param string $duration E.g. "8 hours", "4 days", "1 day 2 nights".
     * @return array{days: int, nights: int, hours: int}
     */
    private function normalize_tour_duration(string $duration): array {
        $result = ['days' => 0, 'nights' => 0, 'hours' => 0];
        if (preg_match('/(\d+)\s*days?/i', $duration, $m)) {
            $result['days'] = (int) $m[1];
        }
        if (preg_match('/(\d+)\s*nights?/i', $duration, $m)) {
            $result['nights'] = (int) $m[1];
        }
        if (preg_match('/(\d+)\s*hours?/i', $duration, $m)) {
            $result['hours'] = (int) $m[1];
        }
        if ($result['days'] === 0 && $result['nights'] === 0 && $result['hours'] === 0 && preg_match('/^(\d+)\s*d/i', $duration, $m)) {
            $result['days'] = (int) $m[1];
        }
        return $result;
    }

    /**
     * Normalize highlights to repeater format.
     *
     * @param array $highlights List of strings or [['highlight' => '...'], ...].
     * @return array<int, array{highlight: string}>
     */
    private function normalize_highlights(array $highlights): array {
        $out = [];
        foreach ($highlights as $h) {
            $text = is_array($h) ? ($h['highlight'] ?? '') : $h;
            if ($text !== '' && $text !== null) {
                $out[] = ['highlight' => (string) $text];
            }
        }
        return $out;
    }

    /**
     * Normalize included/excluded to repeater format.
     *
     * @param array $items List of strings or [['item' => '...'], ...].
     * @param bool  $included If true, add default icon for included items.
     * @return array<int, array{item: string, icon?: string}>
     */
    private function normalize_included_excluded(array $items, bool $included): array {
        $out = [];
        foreach ($items as $x) {
            $text = is_array($x) ? ($x['item'] ?? '') : $x;
            if ($text !== '' && $text !== null) {
                $row = ['item' => (string) $text];
                if ($included) {
                    $row['icon'] = isset($x['icon']) ? $x['icon'] : 'check';
                }
                $out[] = $row;
            }
        }
        return $out;
    }

    /**
     * Create WooCommerce product for tour.
     *
     * @param int   $tour_id Tour ID.
     * @param array $tour    Tour data.
     * @return void
     */
    private function create_woocommerce_product(int $tour_id, array $tour) {
        if (!class_exists('WooCommerce')) {
            return;
        }

        $product = new WC_Product_Simple();

        $product->set_name($tour['title']);
        $product->set_description($tour['content']);
        $product->set_short_description($tour['excerpt']);
        $product->set_regular_price((string) ($tour['price'] ?? 0));

        if (!empty($tour['sale_price'])) {
            $product->set_sale_price((string) $tour['sale_price']);
        }

        $product->set_virtual(true);
        $product->set_downloadable(false);
        $product->set_status('publish');

        $product_id = $product->save();

        if ($product_id) {
            update_post_meta($tour_id, '_ytrip_wc_product_id', $product_id);
            update_post_meta($tour_id, '_ytrip_linked_product_id', $product_id);
        }
    }

    /**
     * Import testimonials.
     *
     * @return void
     */
    private function import_testimonials() {
        $homepage_options = get_option('ytrip_homepage', []);

        $testimonials = [];
        foreach ($this->demo_data['testimonials'] as $testimonial) {
            $testimonials[] = [
                'name' => $testimonial['name'],
                'role' => $testimonial['role'],
                'content' => $testimonial['content'],
                'rating' => $testimonial['rating'],
                'image' => '',
            ];
        }

        $homepage_options['testimonials'] = $testimonials;
        $homepage_options['testimonials_enable'] = true;
        $homepage_options['testimonials_title'] = __('What Our Travelers Say', 'ytrip');

        update_option('ytrip_homepage', $homepage_options);

        $this->import_log['testimonials'] = sprintf(
            /* translators: %d: number of testimonials */
            _n('%d testimonial imported', '%d testimonials imported', count($testimonials), 'ytrip'),
            count($testimonials)
        );
    }

    /**
     * Import settings.
     *
     * @return void
     */
    private function import_settings() {
        $settings = get_option('ytrip_settings', []);
        $settings = array_merge($settings, $this->demo_data['settings']);
        update_option('ytrip_settings', $settings);

        // Set homepage sections
        $homepage = get_option('ytrip_homepage', []);
        $homepage['homepage_sections'] = [
            'enabled' => [
                'hero_slider' => __('Hero Slider', 'ytrip'),
                'search_form' => __('Search & Filter', 'ytrip'),
                'featured_tours' => __('Featured Tours', 'ytrip'),
                'destinations' => __('Popular Destinations', 'ytrip'),
                'categories' => __('Tour Categories', 'ytrip'),
                'testimonials' => __('Customer Reviews', 'ytrip'),
                'stats' => __('Statistics Counter', 'ytrip'),
            ],
            'disabled' => [],
        ];

        update_option('ytrip_homepage', $homepage);

        $this->import_log['settings'] = __('Settings imported successfully', 'ytrip');
    }

    /**
     * Import image from URL.
     *
     * @param string $url   Image URL.
     * @param string $title Image title.
     * @return int|false
     */
    private function import_image(string $url, string $title) {
        // Check cache
        $cache_key = 'demo_image_' . md5($url);
        if (isset($this->image_map[$cache_key])) {
            return $this->image_map[$cache_key];
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = [
            'name' => sanitize_file_name($title) . '.jpg',
            'tmp_name' => $tmp,
        ];

        $id = media_handle_sideload($file_array, 0, $title);

        if (is_wp_error($id)) {
            @unlink($tmp); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            return false;
        }

        // Mark as demo import
        update_post_meta($id, '_ytrip_demo_import', true);

        $this->image_map[$cache_key] = $id;

        return $id;
    }

    /**
     * AJAX remove demo.
     *
     * @return void
     */
    public function ajax_remove_demo() {
        check_ajax_referer('ytrip_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ytrip')]);
        }

        $this->remove_demo_content();

        wp_send_json_success([
            'message' => __('Demo content removed successfully!', 'ytrip'),
        ]);
    }

    /**
     * Remove demo content.
     *
     * @return void
     */
    private function remove_demo_content() {
        $settings = get_option('ytrip_settings', []);
        $tour_slug = $settings['slug_tour'] ?? 'ytrip_tour';
        $destination_slug = $settings['slug_destination'] ?? 'ytrip_destination';
        $category_slug = $settings['slug_category'] ?? 'ytrip_category';

        // Remove tours
        $tours = get_posts([
            'post_type' => $tour_slug,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        foreach ($tours as $tour_id) {
            // Get product ID
            $product_id = get_post_meta($tour_id, '_ytrip_wc_product_id', true);
            if ($product_id) {
                wp_delete_post($product_id, true);
            }
            wp_delete_post($tour_id, true);
        }

        // Remove destination terms
        $destinations = get_terms([
            'taxonomy' => $destination_slug,
            'hide_empty' => false,
        ]);

        if (!is_wp_error($destinations)) {
            foreach ($destinations as $dest) {
                wp_delete_term($dest->term_id, $destination_slug);
            }
        }

        // Remove category terms
        $categories = get_terms([
            'taxonomy' => $category_slug,
            'hide_empty' => false,
        ]);

        if (!is_wp_error($categories)) {
            foreach ($categories as $cat) {
                wp_delete_term($cat->term_id, $category_slug);
            }
        }

        // Remove imported images
        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 100,
            'meta_query' => [
                [
                    'key' => '_ytrip_demo_import',
                    'value' => '1',
                ],
            ],
        ];

        $images = get_posts($args);
        foreach ($images as $image) {
            wp_delete_attachment($image->ID, true);
        }

        // Reset options
        delete_option('ytrip_homepage');
    }

    /**
     * Check if demo is installed.
     *
     * @return bool
     */
    public function is_demo_installed() {
        $settings = get_option('ytrip_settings', []);
        $tour_slug = $settings['slug_tour'] ?? 'ytrip_tour';

        $tours = get_posts([
            'post_type' => $tour_slug,
            'posts_per_page' => 1,
            'fields' => 'ids',
        ]);

        return !empty($tours);
    }

    /**
     * Get import status.
     *
     * @return array
     */
    public function get_import_status() {
        $settings = get_option('ytrip_settings', []);
        $tour_slug = $settings['slug_tour'] ?? 'ytrip_tour';
        $destination_slug = $settings['slug_destination'] ?? 'ytrip_destination';
        $category_slug = $settings['slug_category'] ?? 'ytrip_category';

        return [
            'tours' => wp_count_posts($tour_slug)->publish ?? 0,
            'destinations' => wp_count_terms(['taxonomy' => $destination_slug]) ?? 0,
            'categories' => wp_count_terms(['taxonomy' => $category_slug]) ?? 0,
            'installed' => $this->is_demo_installed(),
        ];
    }
}

// Initialize.
YTrip_Demo_Importer::instance();
