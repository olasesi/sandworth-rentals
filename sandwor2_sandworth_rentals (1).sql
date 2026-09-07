-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 07, 2026 at 09:09 AM
-- Server version: 8.4.10
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sandwor2_sandworth_rentals`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `action` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `entity_id` int UNSIGNED NOT NULL DEFAULT '0',
  `detail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `property_id` int UNSIGNED NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `annual_income` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `employer` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `move_in_date` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `occupants` int UNSIGNED NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `approved_by_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `activated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `property_id`, `status`, `full_name`, `email`, `phone`, `annual_income`, `employer`, `move_in_date`, `occupants`, `notes`, `approved_by_name`, `approved_at`, `submitted_at`, `activated_at`) VALUES
(1, 4, 708, 'approved', 'Mr Oladele Olajide Akintade', 'oladele.akintade@sandworthproperties.ng', '097578800569', '15000000', 'Personal Employment', '2024-11-01', 5, 'I will pay N2,800,000', 'Auto screening', '2026-09-04 13:48:53', '2026-09-04 13:48:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `id` int UNSIGNED NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'currency_code', 'NGN', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(2, 'site_name', 'Sandworth Homes', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(3, 'site_base_url', 'https://rentals.sandworthproperties.ng', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(4, 'default_meta_title', 'Sandworth Homes | Buy, Rent, and Own Property in Nigeria', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(5, 'default_meta_description', 'Sandworth Homes helps Nigerians find homes for sale, annual rentals, and commercial and office spaces with searchable listings and detailed property information', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(6, 'default_share_image', '/public/assets/img/Arepo-II4-1024x576.jpg', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(7, 'robots_policy', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(8, 'contact_email', 'info@sandworthproperties.ng', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(9, 'contact_phone', '+234 803 437 1916', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(10, 'operational_office', 'The Facility Management Office, The Nigeria Army Shopping Complex (The Arena), Bolade-Oshodi, 101233, Lagos State, Nigeria.', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(11, 'registered_office', '1, Tafawa Balewa Crescent, off Adeniran Ogunsanya, Surulere, Lagos State, Nigeria.', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(12, 'facebook_url', 'https://www.facebook.com/sandworthpropertiesltd', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(13, 'instagram_url', 'https://www.instagram.com/sandworthpropertiesltd/', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(14, 'x_url', 'https://x.com/sandworthHomes', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(15, 'linkedin_url', 'https://www.linkedin.com/in/sandworthhomes/', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(16, 'twitter_handle', '', '2026-08-05 15:19:29', '2026-08-07 11:36:46'),
(17, 'platform_bootstrap_version', '2026-08-28-1', '2026-08-10 10:17:58', '2026-08-28 13:07:28');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `property_id` int UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_tickets`
--

CREATE TABLE `maintenance_tickets` (
  `id` int UNSIGNED NOT NULL,
  `tenancy_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `property_id` int UNSIGNED NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `priority` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_tickets`
--

INSERT INTO `maintenance_tickets` (`id`, `tenancy_id`, `user_id`, `property_id`, `status`, `priority`, `title`, `description`, `admin_notes`, `created_at`, `updated_at`, `resolved_at`, `resolved_by_name`) VALUES
(1, 1, 7, 705, 'open', 'high', 'AC not cooling', 'The living room AC powers on but does not cool after 10 minutes.', NULL, '2026-08-28 14:50:45', '2026-08-28 14:50:45', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `property_id` int UNSIGNED NOT NULL DEFAULT '0',
  `sender` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `page_content_blocks`
--

CREATE TABLE `page_content_blocks` (
  `id` int UNSIGNED NOT NULL,
  `page_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `block_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_json` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `page_content_blocks`
--

INSERT INTO `page_content_blocks` (`id`, `page_key`, `block_key`, `content_json`, `created_at`, `updated_at`) VALUES
(1, 'home', 'meta', '{\"pageTitle\":\"Sandworth Homes | Rentals, Homes, and Property Management\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(2, 'home', 'hero', '{\"eyebrow\":\"Sandworth Homes\",\"title\":\"Find homes, rentals & office spaces all in one place.\",\"description\":\"Sandworth Homes brings together spaces designed for Better Living, with a simple renter application system.\"}', '2026-08-04 15:07:48', '2026-08-07 10:16:35'),
(3, 'home', 'stats', '{\"preQualifiedRenters\":612,\"activeRentalsLabel\":\"Active rental inventory\",\"preQualifiedRentersLabel\":\"Pre-qualified renters\",\"avgDaysToLeaseLabel\":\"Average time to lease\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(4, 'home', 'searchModes', '[\"Rent\",\"Buy\",\"Malls & Shops\"]', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(5, 'home', 'panel', '{\"kicker\":\"Rental Manager\",\"title\":\"What the platform supports today\",\"features\":[\"Rental search with filters and detail pages\",\"Rental Dashboard for leads, listings and rent application\",\"Architecture ready for payments, screening and e-signatures\"]}', '2026-08-04 15:07:48', '2026-08-10 09:47:28'),
(6, 'home', 'modules', '{\"eyebrow\":\"Core modules\",\"title\":\"Three product lanes, one platform\",\"linkLabel\":\"See planning flow\",\"cards\":[{\"title\":\"Planning workspace\",\"description\":\"Budget visibility, buyer coordination, milestone tracking, and guided planning for serious property decisions.\"},{\"title\":\"Public marketplace\",\"description\":\"Brand-led homepage, search entry point, category pages, property detail pages, favorites, saved search hooks and SEO-friendly listing URLs.\"},{\"title\":\"Renter experience\",\"description\":\"Filters, map-led discovery, renter hub, online applications, tour scheduling, messaging, payment setup and identity verification.\"},{\"title\":\"Landlord operations\",\"description\":\"Inventory control, lead pipeline, application review, screening, lease generation, payment tracking, maintenance and reporting.\"}]}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(7, 'home', 'explore', '{\"eyebrow\":\"Explore with confidence\",\"title\":\"Choose the path that matches your next move\",\"cards\":[{\"title\":\"Buy a home\",\"description\":\"Search homes, compare neighborhoods, and prepare your next purchase with clearer market context.\",\"link\":\"homes\",\"linkLabel\":\"Browse homes\"},{\"title\":\"Rent a home\",\"description\":\"Find rentals, apply online, and move from approval to payment without leaving the platform.\",\"link\":\"rentals\",\"linkLabel\":\"Browse rentals\"},{\"title\":\"Lease a commercial space\",\"description\":\"Review shopping plazas and retail units with map-based discovery and admin-managed availability.\",\"link\":\"commercial\",\"linkLabel\":\"Browse commercial\"}]}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(8, 'home', 'homesShowcase', '{\"eyebrow\":\"Newly listed homes\",\"title\":\"Fresh homes for sale\",\"linkLabel\":\"See all homes\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(9, 'home', 'rentalsShowcase', '{\"eyebrow\":\"Newly listed rentals\",\"title\":\"Fresh rentals for today\",\"linkLabel\":\"See all rentals\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(10, 'home', 'featured', '{\"eyebrow\":\"Featured inventory\",\"title\":\"Featured listings\",\"linkLabel\":\"Browse all rentals\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(11, 'plan', 'meta', '{\"pageTitle\":\"Plan Your Move | Sandworth Homes\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(12, 'plan', 'hero', '{\"eyebrow\":\"Plan your move\",\"title\":\"Give buyers one place to organize budget, people, and next steps.\",\"description\":\"This planning hub centers on three essentials: understand your finances, align your team, and move through the process with clarity. This version turns those ideas into a company-owned planning workspace.\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(13, 'plan', 'budgetSnapshot', '{\"targetBudget\":\"$420,000\",\"monthlyComfort\":\"$2,850\\/mo\",\"cashToClose\":\"$58,000\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(14, 'plan', 'processSection', '{\"eyebrow\":\"Core sections\",\"title\":\"Mirror the planning journey\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(15, 'plan', 'processSteps', '[{\"title\":\"Financial snapshot\",\"description\":\"Estimate affordability, closing costs, and the payment range that fits your life.\"},{\"title\":\"Build your team\",\"description\":\"Introduce an agent, lender, and transaction support so buyers stop juggling contacts.\"},{\"title\":\"Understand the process\",\"description\":\"Turn a complicated purchase into a timeline with next actions, due dates, and checklists.\"}]', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(16, 'plan', 'milestonesSection', '{\"eyebrow\":\"Milestones\",\"title\":\"Guide users through the buying timeline\",\"note\":\"Turn this into a checklist, reminders engine, and document request flow in the next phase.\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(17, 'plan', 'milestones', '[\"Get pre-approved\",\"Save homes and compare neighborhoods\",\"Schedule tours and shortlist favorites\",\"Prepare offer package\",\"Track inspection, valuation, and closing\"]', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(18, 'plan', 'teamSection', '{\"eyebrow\":\"Build your team\",\"title\":\"People the buyer should see immediately\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(19, 'plan', 'teamMembers', '[{\"title\":\"Buyer advisor\",\"name\":\"Ada Nwosu\",\"note\":\"Guides offer strategy, pricing, and neighborhood fit.\"},{\"title\":\"Mortgage partner\",\"name\":\"Harbor Lending Desk\",\"note\":\"Keeps financing, pre-approval, and monthly affordability in one view.\"},{\"title\":\"Closing coordinator\",\"name\":\"Tomi Adewale\",\"note\":\"Tracks milestones from accepted offer to keys in hand.\"}]', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(20, 'homes', 'meta', '{\"pageTitle\":\"Homes For Sale | Sandworth Homes\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(21, 'homes', 'hero', '{\"eyebrow\":\"Homes for sale\",\"title\":\"Browse homes for sale with a live map.\",\"description\":\"Filter by beds and property type, then browse listings and map pins together so buyers can compare options quickly.\"}', '2026-08-04 15:07:48', '2026-08-28 15:27:55'),
(22, 'homes', 'searchTips', '[\"Search by city, school, or landmark.\",\"Broaden map radius when results feel too narrow.\",\"Save promising homes and compare monthly cost before booking tours.\"]', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(23, 'rentals', 'meta', '{\"pageTitle\":\"Find Rentals | Sandworth Homes\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(24, 'rentals', 'hero', '{\"eyebrow\":\"Renter search center\",\"title\":\"Find Your Next Home.\",\"description\":\"Discover rentals that fit your lifestyle, mapped in real time.\"}', '2026-08-04 15:07:48', '2026-08-04 16:09:23'),
(25, 'commercial', 'meta', '{\"pageTitle\":\"Malls & Shops For Lease | Sandworth Homes\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(26, 'commercial', 'hero', '{\"eyebrow\":\"Commercial leasing\",\"title\":\"Lease a shopping mall unit or shop row on a long-term basis.\",\"description\":\"Browse malls, retail plazas and shop rows available for long lease, mapped by district so you can match footfall and location to your brand.\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(27, 'commercial', 'chips', '[\"Long lease\",\"Anchor & unit leasing\",\"Foot traffic\",\"Fit-out ready\"]', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(28, 'commercial', 'searchTips', '[\"Search by district to find malls and shop rows near your customers.\",\"Long leases (3-10 years) are typical for anchor and mall space.\",\"Contact the leasing team for unit-by-unit pricing on multi-unit malls.\"]', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(29, 'city-rentals', 'meta', '{\"pageTitleSuffix\":\"Rentals | Sandworth Homes\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(30, 'city-rentals', 'hero', '{\"eyebrow\":\"Sandworth Home\",\"description\":\"Listings for homes, retail mall spaces, and commercial properties in your preferred location.\"}', '2026-08-04 15:07:48', '2026-08-05 12:41:31'),
(31, 'city-rentals', 'chips', '[\"For rent\",\"Price\",\"Beds & baths\",\"Property type\",\"Filters\",\"Save search\"]', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(32, 'city-rentals', 'nearbySection', '{\"eyebrow\":\"Nearby Homes\",\"title\":\"Help renters widen their search fast\"}', '2026-08-04 15:07:48', '2026-08-05 12:43:32'),
(33, 'city-rentals', 'nearbyMarkets', '[\"Victoria Island\",\"Ikoyi\",\"Chevron\"]', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(34, 'manager', 'meta', '{\"pageTitle\":\"Rental Manager | Sandworth Homes\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(35, 'manager', 'hero', '{\"eyebrow\":\"Rental manager\",\"title\":\"Operate listings, leads, leases and payments in one place.\",\"description\":\"Use this operating dashboard to manage inventory, review pipeline activity, and oversee tenancy performance across the Sandworth portfolio.\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(36, 'manager', 'pipelineSection', '{\"eyebrow\":\"Leasing pipeline\",\"title\":\"Track renters from inquiry to signed lease\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(37, 'manager', 'roadmap', '{\"eyebrow\":\"Roadmap-ready\",\"title\":\"Back-office modules to add next\",\"items\":[\"Tenant screening integrations\",\"Lease templates and e-signatures\",\"Rent collection and payout ledger\",\"Maintenance ticketing and vendor dispatch\",\"Saved replies, inbox and lead scoring\"]}', '2026-08-04 15:07:48', '2026-08-04 15:07:48'),
(38, 'manager', 'inventorySection', '{\"eyebrow\":\"Inventory snapshot\",\"title\":\"Recent listings in the portfolio\"}', '2026-08-04 15:07:48', '2026-08-04 15:07:48');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `property_id` int UNSIGNED NOT NULL,
  `application_id` int UNSIGNED NOT NULL DEFAULT '0',
  `tenancy_id` int UNSIGNED NOT NULL DEFAULT '0',
  `amount` int UNSIGNED NOT NULL DEFAULT '0',
  `channel` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `card_last4` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reference` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int UNSIGNED NOT NULL,
  `purpose` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `beds` int UNSIGNED NOT NULL DEFAULT '0',
  `baths` decimal(6,2) NOT NULL DEFAULT '0.00',
  `area` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pet_friendly` tinyint(1) NOT NULL DEFAULT '0',
  `available_date` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `images_json` longtext COLLATE utf8mb4_unicode_ci,
  `badges_json` longtext COLLATE utf8mb4_unicode_ci,
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `features_json` longtext COLLATE utf8mb4_unicode_ci,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `monthly_rent` int UNSIGNED NOT NULL DEFAULT '0',
  `service_charge` int UNSIGNED NOT NULL DEFAULT '0',
  `security_deposit` int UNSIGNED NOT NULL DEFAULT '0',
  `asking_price` int UNSIGNED NOT NULL DEFAULT '0',
  `commercial_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lease_term` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `units` int UNSIGNED NOT NULL DEFAULT '0',
  `floors` int UNSIGNED NOT NULL DEFAULT '0',
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `listed_by` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `purpose`, `title`, `location`, `type`, `beds`, `baths`, `area`, `pet_friendly`, `available_date`, `image`, `images_json`, `badges_json`, `summary`, `features_json`, `lat`, `lng`, `monthly_rent`, `service_charge`, `security_deposit`, `asking_price`, `commercial_type`, `lease_term`, `units`, `floors`, `status`, `listed_by`, `created_at`, `updated_at`) VALUES
(701, 'rent', 'Sandworth Estate', 'Karu, Abuja', 'Residential', 0, 0.00, '38,000 sqft', 0, 'Available for lease now', '/public/assets/uploads/rentals/sandworth-estate-main-1787921281-1985.png', '[\"\\/public\\/assets\\/uploads\\/rentals\\/sandworth-estate-main-1787921281-1985.png\",\"\\/public\\/assets\\/uploads\\/rentals\\/sandworth-estate-gallery-1-1786117605-9279.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/sandworth-estate-gallery-2-1786117605-6711.jpg\"]', '[\"Residential\",\"For Lease\"]', 'Sandworth Estate, Karu, Abuja is a 342 units of housing estate sitting on 119,700sqm that offers premium class apartments yet very affordable. It’s appearance and fitting boasts of impeccable finishing, elegance presence, comfort and luxury.', '[\"2-beroom flats\",\"3-bedroom flats\",\"Terrace duplexes\",\"Detached houses\",\"Semi-detached houses\",\"Schools\",\"supermarkets\",\"Gym\",\"Clinic\",\"FM Office\",\"Lawn Tennis\",\"Play Ground\",\"Swimming Pool\",\"Water Treatment Plant\",\"Electrical Room\"]', 6.4507000, 3.5560000, 6000000, 0, 500000, 5500000, 'Shopping Mall', '5-10 year lease', 46, 3, 'available', 'Sandworth Admin', '2026-08-04 15:07:48', '2026-08-28 12:48:01'),
(702, 'sale', 'Sandworth Court Arepo', 'Arepo, Ogun State', 'Residential', 2, 2.00, '14,500 sqft', 0, 'Available Sep 01, 2026', '/public/assets/uploads/homes/sandworth-court-arepo-main-1787916426-5126.png', '[\"\\/public\\/assets\\/uploads\\/homes\\/sandworth-court-arepo-main-1787916426-5126.png\",\"\\/public\\/assets\\/uploads\\/homes\\/sandworth-court-arepo-gallery-1-1787916889-7138.jpg\",\"\\/public\\/assets\\/uploads\\/homes\\/sandworth-court-arepo-gallery-1-1787916813-1285.jpg\",\"\\/public\\/assets\\/uploads\\/homes\\/sandworth-court-arepo-gallery-1-1787916638-4859.jpg\",\"\\/public\\/assets\\/uploads\\/homes\\/sandworth-court-arepo-gallery-1-1787916557-2228.png\"]', '[\"Long lease\",\"Prime frontage\",\"Banking hall ready\"]', 'This is situated in a serene and organized environment at the boundary of Lagos and Ogun State, hosting many estates including Journalist Estate and Citi-View Estate etc. It is a private owned estate that seeks to reinvent the concept of old G.R.A. with cutting edge architecture and delivers high class living standards at an affordable price.', '[\"Street-level frontage\",\"Passenger lift\",\"Fibre internet ready\",\"Dedicated generator\",\"Basement parking\"]', 6.4294000, 3.4219000, 6000000, 0, 500000, 5500000, 'Retail Plaza', '3-7 year lease', 18, 2, 'available', 'Sandworth Admin', '2026-08-04 15:07:48', '2026-08-28 11:34:49'),
(703, 'commercial', 'L\'Arcade Mall Owerri', 'Owerri, Imo State', 'Mall', 0, 0.00, '4,800 sqft', 0, 'Available now', '/public/assets/uploads/commercial/l-arcade-mall-owerri-main-1787917225-8179.jpg', '[\"\\/public\\/assets\\/uploads\\/commercial\\/l-arcade-mall-owerri-main-1787917225-8179.jpg\",\"\\/public\\/assets\\/uploads\\/commercial\\/l-arcade-mall-owerri-gallery-1-1787917423-5762.jpg\",\"\\/public\\/assets\\/uploads\\/commercial\\/l-arcade-mall-owerri-main-1786106192-7222.jpg\"]', '[\"Long lease\",\"Cinema-ready shell\",\"Escalator access\"]', 'L’ARCADE is an enclosed centre located approximately 5 minutes from Control and it’s a 3-minute drive from the popular Concorde Hotel in Owerri. The locational advantage of the site is unparalleled due to its central disposition and accessibility from all quarters of Owerri Metropolis. The Centre provides lettable area of 12sqm of over 400 stalls targeting amongst others to attract outlets like Furniture, Clothing, Shoes and Bags, Electronics, Food & Drinks, Supermarkets and Pharmacy and counting from local and international owners. L’ARCADE’s innovative design, connects the dynamic urban surroundings to the history and splendour of the city.', '[\"Cinema shell unit\",\"Mid-mall kiosk bays\",\"Escalators\",\"Loading bay\",\"Central AC\",\"CCTV network\"]', 6.6018000, 3.3515000, 6000000, 180000, 500000, 5500000, 'Shopping Mall', '1 - 5 year lease, renewable', 400, 3, 'available', 'Sandworth Admin', '2026-08-04 15:07:48', '2026-08-28 11:44:04'),
(705, 'rent', 'Abraham Adesanya - 4 bedroom semi-detached', 'Abraham Adesanya, Lekki-Epe Expressway', 'Apartment', 4, 3.00, '9,800 sqft', 0, 'Available now', '/public/assets/uploads/rentals/abraham-adesanya-4-bedroom-semi-detached-main-1787918057-1872.jpg', '[\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-semi-detached-main-1787918057-1872.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-semi-detached-gallery-1-1787918380-5137.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-semi-detached-gallery-1-1787918183-8338.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-semi-detached-gallery-1-1787918143-7333.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-semi-detached-main-1785854263-6259.jpg\"]', '[\"Long lease\",\"Tech corridor\",\"Co-retail friendly\"]', 'This prestigious estate sits on a land mass of approximately 6,917.709 sqm. Access through the dual carriageway of the Lekki-Epe Expressway.', '[\"Open-plan shells\",\"Fibre internet ready\",\"Shared loading dock\",\"Rooftop signage\"]', 6.5095000, 3.3711000, 6000000, 500, 500000, 5500000, 'Apartment', '3-10 year lease', 12, 2, 'available', 'Sandworth Admin', '2026-08-04 15:07:48', '2026-08-28 11:59:40'),
(706, 'rent', 'Abraham Adesanya - 4 bedroom terrace builder\'s finish (corner piece)', 'Abraham Adesanya, Lekki-Epe Expressway', 'Apartment', 4, 3.00, '9,800 sqft', 0, 'Available now', '/public/assets/uploads/rentals/abraham-adesanya-4-bedroom-terrace-builder-s-finish-corner-piece-main-1787917925-2870.jpg', '[\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-terrace-builder-s-finish-corner-piece-main-1787917925-2870.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-terrace-builder-s-finish-corner-piece-gallery-1-1787917973-7838.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-terrace-builder-s-finish-corner-piece-gallery-1-1785859658-5136.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-terrace-builder-s-finish-corner-piece-gallery-1-1785859272-5117.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-terrace-builder-s-finish-corner-piece-gallery-1-1785859140-9102.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-terrace-builder-s-finish-corner-piece-gallery-1-1785859090-1737.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/abraham-adesanya-4-bedroom-terrace-builder-s-finish-corner-piece-gallery-1-1785858828-6436.jpg\"]', '[\"New\",\"Managed by Sandworth\",\"Tour today\"]', 'Abraham Adesanya, Lekki-Epe Expressway, is a prime location for luxury estates because it is a well-planned residential government scheme.', '[]', 6.4474000, 3.4724000, 6000000, 500000, 500000, 5500000, 'Apartment', '1-10 year lease', 1, 1, 'available', 'Sandworth Admin', '2026-08-04 17:53:48', '2026-08-28 11:52:53'),
(707, 'sale', 'Sandworth Gardens', 'Owerri · Imo State', 'Residential', 0, 0.00, '5850 sqft', 0, 'Available now', '/public/assets/uploads/homes/sandworth-gardens-main-1787930159-2484.jpg', '[\"\\/public\\/assets\\/uploads\\/homes\\/sandworth-gardens-main-1787930159-2484.jpg\",\"\\/public\\/assets\\/uploads\\/homes\\/sandworth-gardens-gallery-1-1787931018-3391.jpg\",\"\\/public\\/assets\\/uploads\\/homes\\/sandworth-gardens-gallery-1-1787930528-7915.jpg\",\"\\/public\\/assets\\/uploads\\/homes\\/sandworth-gardens-main-1786350677-8658.jpg\"]', '[\"Gardens\",\"For Sale\"]', 'This prestigious estate is sitting on a land area of approximately 5850 Sqm, and it is located at Urata Egbu layout, Owerri in Imo State, it can be accessed either through Toronto Junction by Wethedral Road or the Road Safety Roundabout by Airport Road. Sandworth Gardens represent luxury and Style. The topologies of the estate guarantees comfort, serenity and tranquility.', '[\"24 hours\",\"security network\",\"Perimeter fencing with Gate\",\"House Street light\\/illumination\",\"Electrical Room\",\"Drainage network\",\"Playground and Recreational area\",\"Transformers for Electricity\",\"Full Capacity Generator\"]', 6.4474000, 3.4724000, 6000000, 0, 500000, 5500000, 'Apartment', '5-10 year lease', 0, 0, 'available', 'Sandworth Admin', '2026-08-10 08:31:17', '2026-08-28 15:30:18'),
(708, 'rent', 'En-suite semi-detached duplex', 'Ajah, Lagos State', 'Sandworth Homes, Ajah', 3, 3.00, '350sqm', 0, 'Available now', '/public/assets/uploads/rentals/en-suite-semi-detached-duplex-main-1788528347-5951.jpg', '[\"\\/public\\/assets\\/uploads\\/rentals\\/en-suite-semi-detached-duplex-main-1788528347-5951.jpg\",\"\\/public\\/assets\\/uploads\\/rentals\\/en-suite-semi-detached-duplex-gallery-1-1788528347-2960.jpg\"]', '[\"Long lease\",\"Prime frontage\",\"Banking hall ready\"]', 'This Prestigious estate is sitting on a land mass area of approximately 6917.709 Square meters. Access to the estate is through the dual carriage way of the Lekki -Epe expressway. Sandworth homes represent luxury and class. The topology of the houses guarantees comfort, serenity and premium luxury that you desire. The interiors are magnificently finished with impeccable detailing and design. The Estate comprises of 35 units of four (4) En-suite luxury bedrooms [Terraces] and two (2) units of five (5) En-suite semi-detached duplex. The ambiance of our estates exudes a design pattern of simplicity, luxury, aesthetic and bespoke finishing. The five (5) bedroom Semi-detached is unique to those who seek extra touch of royalty.', '[\"Smart Home Technology\",\"Private Garden per Unit\",\"Clubhouse & Gym\",\"Gated 24\\/7 Security\",\"Paved Internal Roads\",\"Backup Power & Water\",\"C of O Title\"]', 6.4474000, 3.4724000, 5500000, 350000, 500000, 4800000, 'Apartment', '5-10 year lease', 35, 2, 'available', 'Sandworth Admin', '2026-09-04 13:25:47', '2026-09-04 13:25:47');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_offers`
--

CREATE TABLE `purchase_offers` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `property_id` int UNSIGNED NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `offer_amount` int UNSIGNED NOT NULL DEFAULT '0',
  `terms` text COLLATE utf8mb4_unicode_ci,
  `timeline` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_searches`
--

CREATE TABLE `saved_searches` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `purpose` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rent',
  `location` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `beds` int UNSIGNED NOT NULL DEFAULT '0',
  `property_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `commercial_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pet_friendly` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `saved_searches`
--

INSERT INTO `saved_searches` (`id`, `user_id`, `name`, `purpose`, `location`, `beds`, `property_type`, `commercial_type`, `pet_friendly`, `created_at`, `updated_at`) VALUES
(1, 7, 'Lekki rentals', 'rent', 'Lekki', 4, '', '', 1, '2026-08-28 14:50:45', '2026-08-28 14:50:45'),
(2, 4, 'Rent in Ajah', 'rent', 'Ajah', 0, '', '', 0, '2026-09-04 12:52:51', '2026-09-04 12:52:51');

-- --------------------------------------------------------

--
-- Table structure for table `tenancies`
--

CREATE TABLE `tenancies` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `property_id` int UNSIGNED NOT NULL,
  `application_id` int UNSIGNED NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `start_date` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `monthly_rent` int UNSIGNED NOT NULL DEFAULT '0',
  `service_charge` int UNSIGNED NOT NULL DEFAULT '0',
  `security_deposit` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tenancy_ledger`
--

CREATE TABLE `tenancy_ledger` (
  `id` int UNSIGNED NOT NULL,
  `tenancy_id` int UNSIGNED NOT NULL,
  `label` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `charge_type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int UNSIGNED NOT NULL DEFAULT '0',
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid',
  `payment_reference` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `paid_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tour_requests`
--

CREATE TABLE `tour_requests` (
  `id` int UNSIGNED NOT NULL,
  `property_id` int UNSIGNED NOT NULL,
  `slot_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_by_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tour_requests`
--

INSERT INTO `tour_requests` (`id`, `property_id`, `slot_id`, `user_id`, `status`, `full_name`, `email`, `phone`, `message`, `admin_notes`, `created_at`, `updated_at`, `confirmed_at`, `confirmed_by_name`, `completed_at`, `cancelled_at`) VALUES
(1, 707, 1, 3, 'requested', 'Opeyemi Tosin', 'opeyemitosinakinluyi@yahoo.com', '07017870050', 'I will be available for this tour', NULL, '2026-08-28 17:53:38', '2026-08-28 17:53:38', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tour_slots`
--

CREATE TABLE `tour_slots` (
  `id` int UNSIGNED NOT NULL,
  `property_id` int UNSIGNED NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_by_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tour_slots`
--

INSERT INTO `tour_slots` (`id`, `property_id`, `status`, `starts_at`, `ends_at`, `notes`, `created_by_name`, `created_at`) VALUES
(1, 707, 'open', '2026-08-29 10:00:00', '2026-08-29 12:47:00', 'The tour starts at 10a.m, parking slots will be provided for those coming by vehicle.', 'Sandworth Admin', '2026-08-28 17:48:58'),
(2, 706, 'open', '2026-08-29 10:00:00', '2026-08-29 10:30:00', 'Viewing block', 'Sandworth Admin', '2026-08-28 17:52:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `name`, `email`, `phone`, `password_hash`, `created_at`) VALUES
(1, 'admin', 'Sandworth Admin', 'admin@sandworthliving.test', '+234 800 000 0000', '$2y$10$vqn4naFRj7PXiFVaXqWNueClmv6qoxuLJKa2wy5dZPcYtBQ9ROHhm', '2026-07-31 22:26:54'),
(2, 'user', 'Opeyemi Akinluyi', 'opeyemitosinakinluyi@gmail.com', '07032270305', '$2y$10$7KDwmQSWdsreJShlL2UKs.vVLMI3t1n5gH157sfPP5H40kiVQpsKO', '2026-08-04 15:45:38'),
(3, 'user', 'Opeyemi Tosin', 'opeyemitosinakinluyi@yahoo.com', '07017870050', '$2y$10$oK86MjXwhpwpSmLQR16WMubxKjUh9yq6CtHIIAk2sjpCMSjiAyP.G', '2026-08-28 15:48:52'),
(4, 'user', 'Mr Oladele Olajide Akintade', 'oladele.akintade@sandworthproperties.ng', '097578800569', '$2y$10$ARlep722KQPVidZmwA6kceXx3/UfuVQFPhW4qdFdmoUBlaF00Q5iC', '2026-09-04 12:52:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_log_user_idx` (`user_id`),
  ADD KEY `activity_log_entity_idx` (`entity_type`,`entity_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applications_user_idx` (`user_id`),
  ADD KEY `applications_property_idx` (`property_id`),
  ADD KEY `applications_status_idx` (`status`);

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `app_settings_setting_key_unique` (`setting_key`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `favorites_user_property_unique` (`user_id`,`property_id`),
  ADD KEY `favorites_user_idx` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_user_idx` (`user_id`),
  ADD KEY `messages_property_idx` (`property_id`),
  ADD KEY `messages_sender_idx` (`sender`);

--
-- Indexes for table `page_content_blocks`
--
ALTER TABLE `page_content_blocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_content_blocks_unique` (`page_key`,`block_key`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_tenancy_idx` (`tenancy_id`),
  ADD KEY `payments_application_idx` (`application_id`),
  ADD KEY `payments_user_idx` (`user_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `properties_purpose_idx` (`purpose`),
  ADD KEY `properties_status_idx` (`status`);

--
-- Indexes for table `purchase_offers`
--
ALTER TABLE `purchase_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_offers_user_idx` (`user_id`),
  ADD KEY `purchase_offers_property_idx` (`property_id`),
  ADD KEY `purchase_offers_status_idx` (`status`);

--
-- Indexes for table `saved_searches`
--
ALTER TABLE `saved_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `saved_searches_user_idx` (`user_id`),
  ADD KEY `saved_searches_purpose_idx` (`purpose`);

--
-- Indexes for table `tenancies`
--
ALTER TABLE `tenancies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenancies_user_idx` (`user_id`),
  ADD KEY `tenancies_application_idx` (`application_id`),
  ADD KEY `tenancies_status_idx` (`status`);

--
-- Indexes for table `tenancy_ledger`
--
ALTER TABLE `tenancy_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenancy_ledger_tenancy_idx` (`tenancy_id`);

--
-- Indexes for table `tour_requests`
--
ALTER TABLE `tour_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tour_requests_property_idx` (`property_id`),
  ADD KEY `tour_requests_slot_idx` (`slot_id`),
  ADD KEY `tour_requests_user_idx` (`user_id`),
  ADD KEY `tour_requests_status_idx` (`status`),
  ADD KEY `tour_requests_created_at_idx` (`created_at`);

--
-- Indexes for table `tour_slots`
--
ALTER TABLE `tour_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tour_slots_property_idx` (`property_id`),
  ADD KEY `tour_slots_status_idx` (`status`),
  ADD KEY `tour_slots_starts_at_idx` (`starts_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `app_settings`
--
ALTER TABLE `app_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_content_blocks`
--
ALTER TABLE `page_content_blocks`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=709;

--
-- AUTO_INCREMENT for table `purchase_offers`
--
ALTER TABLE `purchase_offers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_searches`
--
ALTER TABLE `saved_searches`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tenancies`
--
ALTER TABLE `tenancies`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenancy_ledger`
--
ALTER TABLE `tenancy_ledger`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tour_requests`
--
ALTER TABLE `tour_requests`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tour_slots`
--
ALTER TABLE `tour_slots`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
