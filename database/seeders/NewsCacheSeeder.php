<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\NewsCache;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class NewsCacheSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $germany = Country::where('iso_code', 'DE')->value('id');
        $china = Country::where('iso_code', 'CN')->value('id');
        $indonesia = Country::where('iso_code', 'ID')->value('id');
        $australia = Country::where('iso_code', 'AU')->value('id');
        $us = Country::where('iso_code', 'US')->value('id');
        $japan = Country::where('iso_code', 'JP')->value('id');
        $singapore = Country::where('iso_code', 'SG')->value('id');
        $malaysia = Country::where('iso_code', 'MY')->value('id');
        $uk = Country::where('iso_code', 'GB')->value('id');
        $india = Country::where('iso_code', 'IN')->value('id');

        $news = [
            ['country_id' => $germany, 'title' => 'German Manufacturing Sector Shows Resilient Growth in Q2', 'description' => 'German industrial production exceeded expectations as export orders from Asia and North America surged, reinforcing the country economic stability.', 'source' => 'Reuters', 'url' => 'https://reuters.com', 'published_at' => (clone $now)->subHours(2), 'sentiment' => 'positive'],
            ['country_id' => $germany, 'title' => 'Germany Logistics Hub Hamburg Reports Record Container Volume', 'description' => 'The Port of Hamburg handled record container volumes this quarter, signaling strong trade flows between Europe and major Asian markets.', 'source' => 'TradeWinds', 'url' => 'https://tradewindsnews.com', 'published_at' => (clone $now)->subHours(6), 'sentiment' => 'positive'],
            ['country_id' => $germany, 'title' => 'ECB Monetary Policy Impact on German Export Competitiveness', 'description' => 'Economists analyze how recent ECB interest rate decisions are affecting Germanys export-driven economy and cross-border trade dynamics.', 'source' => 'Financial Times', 'url' => 'https://ft.com', 'published_at' => (clone $now)->subHours(12), 'sentiment' => 'neutral'],
            ['country_id' => $china, 'title' => 'China Belt and Road Initiative Expands Maritime Trade Routes', 'description' => 'New shipping lanes under the Belt and Road Initiative are set to reduce transit times between China and Europe, benefiting global supply chains.', 'source' => 'Xinhua', 'url' => 'https://xinhuanet.com', 'published_at' => (clone $now)->subHours(1), 'sentiment' => 'positive'],
            ['country_id' => $china, 'title' => 'Chinese Port Congestion Eases as Export Demand normalizes', 'description' => 'After months of backlog, major Chinese ports including Shanghai and Ningbo report improved throughput as global demand stabilizes.', 'source' => 'Lloyd List', 'url' => 'https://lloydslist.com', 'published_at' => (clone $now)->subHours(4), 'sentiment' => 'positive'],
            ['country_id' => $china, 'title' => 'Trade Tensions Impact China Technology Supply Chains', 'description' => 'New export control measures continue to reshape semiconductor and technology supply chains across the Asia-Pacific region.', 'source' => 'Nikkei Asia', 'url' => 'https://asia.nikkei.com', 'published_at' => (clone $now)->subHours(10), 'sentiment' => 'negative'],
            ['country_id' => $indonesia, 'title' => 'Indonesia Nickel Export Ban Drives Domestic Processing Boom', 'description' => 'Indonesia downstream processing policy has attracted billions in investment for nickel and EV battery manufacturing facilities.', 'source' => 'Jakarta Post', 'url' => 'https://thejakartapost.com', 'published_at' => (clone $now)->subHours(3), 'sentiment' => 'positive'],
            ['country_id' => $indonesia, 'title' => 'Logistics Infrastructure Development in Indonesia Archipelago', 'description' => 'The government sea toll program continues to improve connectivity across Indonesian islands, reducing logistics costs for remote regions.', 'source' => 'Antara News', 'url' => 'https://antaranews.com', 'published_at' => (clone $now)->subHours(8), 'sentiment' => 'positive'],
            ['country_id' => $indonesia, 'title' => 'Indonesia Inflation Remains Stable Amid Global Uncertainty', 'description' => 'Bank Indonesia reports consumer price index within target range, supported by careful monetary policy and stable food supply chains.', 'source' => 'Bloomberg', 'url' => 'https://bloomberg.com', 'published_at' => (clone $now)->subHours(16), 'sentiment' => 'neutral'],
            ['country_id' => $australia, 'title' => 'Australia Iron Ore Exports to China Rebound Strongly', 'description' => 'Australian mining exports have recovered as Chinese steel production ramps up, boosting trade volumes through major Western Australian ports.', 'source' => 'Sydney Morning Herald', 'url' => 'https://smh.com.au', 'published_at' => (clone $now)->subHours(2), 'sentiment' => 'positive'],
            ['country_id' => $australia, 'title' => 'Supply Chain Diversification Boosts Australia Logistics Sector', 'description' => 'As companies adopt China-plus-one strategies, Australian logistics providers are seeing increased demand for warehousing and distribution.', 'source' => 'Australian Financial Review', 'url' => 'https://afr.com', 'published_at' => (clone $now)->subHours(7), 'sentiment' => 'positive'],
            ['country_id' => $us, 'title' => 'US West Coast Ports Modernize to Handle Mega-Ships', 'description' => 'Major investments in port infrastructure along the US West Coast aim to accommodate larger vessels and reduce supply chain bottlenecks.', 'source' => 'Journal of Commerce', 'url' => 'https://joc.com', 'published_at' => (clone $now)->subHours(1), 'sentiment' => 'positive'],
            ['country_id' => $us, 'title' => 'Federal Reserve Assessment of Trade Policy Impact on Economy', 'description' => 'The Fed analysis suggests that ongoing trade policy adjustments could affect inflation trajectories and global supply chain patterns.', 'source' => 'Wall Street Journal', 'url' => 'https://wsj.com', 'published_at' => (clone $now)->subHours(5), 'sentiment' => 'neutral'],
            ['country_id' => $us, 'title' => 'US Manufacturing Renaissance Reshapes Logistics Networks', 'description' => 'Domestic manufacturing incentives are driving a shift in logistics patterns with new distribution hubs emerging across the Sun Belt region.', 'source' => 'Supply Chain Dive', 'url' => 'https://supplychaindive.com', 'published_at' => (clone $now)->subHours(14), 'sentiment' => 'positive'],
            ['country_id' => $japan, 'title' => 'Japan Automotive Supply Chain Adapts to EV Transition', 'description' => 'Japanese automakers are restructuring their global supply chains to accommodate the shift toward electric vehicle production and battery sourcing.', 'source' => 'Reuters', 'url' => 'https://reuters.com', 'published_at' => (clone $now)->subHours(3), 'sentiment' => 'neutral'],
            ['country_id' => $japan, 'title' => 'Yen Fluctuations Impact Japan Import and Export Dynamics', 'description' => 'Currency market volatility is affecting trade balances with Japanese exporters gaining competitiveness while import costs rise.', 'source' => 'Nikkei', 'url' => 'https://nikkei.com', 'published_at' => (clone $now)->subHours(9), 'sentiment' => 'negative'],
            ['country_id' => $singapore, 'title' => 'Singapore Port Reclaims Top Transshipment Hub Status', 'description' => 'The Port of Singapore reported increased transshipment volumes as global shipping lines optimize routes through Southeast Asia.', 'source' => 'Straits Times', 'url' => 'https://straitstimes.com', 'published_at' => (clone $now)->subHours(2), 'sentiment' => 'positive'],
            ['country_id' => $singapore, 'title' => 'Trade Finance Innovation in Singapore Boosts Regional Commerce', 'description' => 'Singapore financial institutions are leveraging blockchain technology to streamline trade finance and reduce cross-border transaction costs.', 'source' => 'Business Times', 'url' => 'https://businesstimes.com.sg', 'published_at' => (clone $now)->subHours(11), 'sentiment' => 'positive'],
            ['country_id' => $malaysia, 'title' => 'Malaysia Semiconductor Exports Drive Economic Growth', 'description' => 'Malaysia electronics and semiconductor exports continue to grow, positioning the country as a key node in the global chip supply chain.', 'source' => 'The Edge Malaysia', 'url' => 'https://theedgemalaysia.com', 'published_at' => (clone $now)->subHours(4), 'sentiment' => 'positive'],
            ['country_id' => $malaysia, 'title' => 'Port Klang Expansion to Capture More Transshipment Traffic', 'description' => 'The expansion of Port Klang container facilities aims to attract additional transshipment volume from the Strait of Malacca shipping lane.', 'source' => 'MarineLink', 'url' => 'https://marinelink.com', 'published_at' => (clone $now)->subHours(15), 'sentiment' => 'positive'],
            ['country_id' => $uk, 'title' => 'UK Post-Brexit Trade Deals Open New Logistics Corridors', 'description' => 'New trade agreements with Indo-Pacific nations are creating additional shipping routes and logistics opportunities for UK-based traders.', 'source' => 'BBC News', 'url' => 'https://bbc.com', 'published_at' => (clone $now)->subHours(2), 'sentiment' => 'positive'],
            ['country_id' => $uk, 'title' => 'London Shipping Market Adapts to Regulatory Changes', 'description' => 'The London maritime services sector is adjusting to new environmental regulations affecting shipping routes and operational costs.', 'source' => 'Lloyds List', 'url' => 'https://lloydslist.com', 'published_at' => (clone $now)->subHours(13), 'sentiment' => 'neutral'],
            ['country_id' => $india, 'title' => 'India Infrastructure Push Transforms Freight Logistics', 'description' => 'The dedicated freight corridor project and new expressways are significantly reducing cargo transit times across the Indian subcontinent.', 'source' => 'Times of India', 'url' => 'https://timesofindia.com', 'published_at' => (clone $now)->subHours(1), 'sentiment' => 'positive'],
            ['country_id' => $india, 'title' => 'India Emerges as Alternative Manufacturing Hub for Global Brands', 'description' => 'Major multinational corporations are expanding manufacturing operations in India as part of supply chain diversification strategies.', 'source' => 'Economic Times', 'url' => 'https://economictimes.com', 'published_at' => (clone $now)->subHours(6), 'sentiment' => 'positive'],
            ['country_id' => $india, 'title' => 'Indian Port Modernization Program Targets Efficiency Gains', 'description' => 'The Sagarmala project upgrades at major Indian ports aim to reduce turnaround times and improve trade competitiveness.', 'source' => 'Hindu Business Line', 'url' => 'https://thehindubusinessline.com', 'published_at' => (clone $now)->subHours(18), 'sentiment' => 'positive'],
        ];

        NewsCache::truncate();

        foreach ($news as $item) {
            NewsCache::create($item);
        }
    }
}
