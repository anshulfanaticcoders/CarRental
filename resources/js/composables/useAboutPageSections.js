import { computed } from 'vue';

const unsplash = {
    hero: 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=2200&q=82',
    panel: 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1200&q=82',
    sidePanel: 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=800&q=82',
    journey: 'https://images.unsplash.com/photo-1525609004556-c46c7d6cf023?auto=format&fit=crop&w=1500&q=82',
    ribbon: 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1800&q=82',
    city: 'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?auto=format&fit=crop&w=1400&q=82',
    route: 'https://images.unsplash.com/photo-1519003722824-194d4455a60c?auto=format&fit=crop&w=900&q=82',
    openRoad: 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?auto=format&fit=crop&w=900&q=82',
};

const defaults = {
    hero: {
        badge: 'Vrooem car rentals',
        title: 'Built for every mile between booking and return.',
        content: '<p>We connect travelers with trusted rental partners, clear prices, useful protection, and support that stays close from airport counter to final drop-off.</p>',
        imageUrl: unsplash.hero,
        imageAlt: 'Sports car driving on an open road at dusk',
        primaryButtonText: 'Explore the story',
        primaryButtonUrl: '#story',
        secondaryButtonText: 'See what we handle',
        secondaryButtonUrl: '#promise',
        panelImageUrl: unsplash.panel,
        panelImageAlt: 'Car moving along a mountain road at dusk',
        panelTitle: 'Pickup, protected',
        panelText: 'Live support and clear rental terms.',
        sideImageUrl: unsplash.sidePanel,
        sideImageAlt: 'Red sports car detail under city light',
    },
    stats: [
        { number: '800', suffix: '+', label: 'Providers worldwide' },
        { number: '180', suffix: '+', label: 'Countries covered' },
        { number: '24', suffix: '/7', label: 'Travel support' },
        { number: '250', suffix: 'K+', label: 'Trips completed' },
    ],
    mission: {
        kicker: 'Why we exist',
        title: 'The rental counter should not be the first place a driver learns the rules.',
        content: '<p>Vrooem is built for the gap between finding a car and feeling ready to collect it. We make the important rental details easier to compare before the trip starts.</p>',
        proofItems: [
            { icon: 'building', title: 'Supplier fit', description: 'Real pickup locations and readable terms.' },
            { icon: 'car', title: 'Trip fit', description: 'Vehicle classes matched to route and luggage.' },
            { icon: 'message', title: 'Support fit', description: 'Help for deposits, delays, and returns.' },
        ],
        missionLines: [
            { label: 'Before pickup', title: 'Drivers need the full rental picture early.', description: 'Fuel policy, deposit, mileage, protection, documents, and pickup notes should be visible before a booking feels final.' },
            { label: 'At the counter', title: 'Confidence comes from fewer surprises.', description: 'Clear confirmations help customers know what to bring, what is included, and what optional extras may be offered.' },
            { label: 'On the road', title: 'Travel plans move, support should move too.', description: 'When flights shift, pickup windows change, or return questions appear, the experience should still feel handled.' },
        ],
    },
    journey: {
        kicker: 'How it works',
        title: 'A cleaner rental journey, built around the moments that can go wrong.',
        content: '<p>The page should tell customers that Vrooem is not only a listing grid. It is a layer of confidence around supplier choice, booking details, payment expectations, and travel-day support.</p>',
        imageUrl: unsplash.journey,
        imageAlt: 'Car headlights on a winding night road',
        routeNoteTitle: 'From search to return, each step has context.',
        routeNoteText: 'Show the driver what matters: fuel policy, deposit, included mileage, protection choices, counter instructions, and support routes.',
        items: [
            { title: 'Compare real options', description: 'Vehicle class, pickup location, fuel policy, mileage, protection, and supplier terms are presented before booking.' },
            { title: 'Book with fewer surprises', description: 'Confirmation details stay readable, so drivers understand what to bring and what to expect at the counter.' },
            { title: 'Get help when plans move', description: 'Delays, flight changes, extensions, and return questions get routed to support instead of leaving travelers guessing.' },
        ],
    },
    ribbon: {
        title: 'A rental brand with travel instincts, not only vehicle inventory.',
        content: '<p>Great rental experiences depend on details: where the desk is, what the deposit means, how late pickup works, and who answers when the itinerary changes.</p>',
        backgroundImageUrl: unsplash.ribbon,
        backgroundImageAlt: 'Car moving through a mountain road',
    },
    promises: {
        kicker: 'What customers should know',
        title: 'The promises worth putting on an About page.',
        content: '<p>This concept focuses on the information a car rental company should say clearly: trust, coverage, support, pricing, supplier quality, safety, and easy pickup.</p>',
        items: [
            { icon: 'building', kicker: 'Trusted supply', title: 'Partners with real pickup operations', description: 'Supplier quality matters when a customer is standing at an airport desk with luggage and limited time.' },
            { icon: 'receipt', kicker: 'Clear checkout', title: 'Pricing that explains itself', description: 'Display inclusions, optional extras, protection, deposits, and key terms before the customer commits.' },
            { icon: 'clock', kicker: 'Trip confidence', title: 'Support for timing changes', description: 'Flights move, queues happen, and pickup windows matter. The brand should feel ready for that reality.' },
            { icon: 'shield', kicker: 'Driver care', title: 'Protection choices made readable', description: 'Drivers should understand coverage, damage protection, excess amounts, and what happens if a car needs replacement.' },
        ],
    },
    coverage: {
        kicker: 'Where Vrooem fits',
        title: 'Built for airports, city desks, family routes, and last-minute detours.',
        content: '<p>A strong About page should help every visitor understand the same thing: Vrooem brings rental options, terms, and support into one calmer travel layer.</p>',
        items: [
            { imageUrl: unsplash.city, imageAlt: 'Premium car parked on a city street at night', title: 'City rentals', description: 'Short appointments, business trips, and weekend plans need quick comparison and clear pickup notes.' },
            { imageUrl: unsplash.route, imageAlt: 'Car driving through a scenic mountain road', title: 'Longer routes', description: 'Mileage, luggage space, comfort, and fuel policy become part of the trip plan.' },
            { imageUrl: unsplash.openRoad, imageAlt: 'Classic car on an open road with a dramatic sky', title: 'Open-road bookings', description: 'Travelers can choose with confidence when terms are shown before the counter.' },
        ],
    },
    cta: {
        kicker: 'Review direction',
        title: 'A cinematic About page that sells trust before it sells cars.',
        content: '<p>Use this static demo to judge mood, motion, image treatment, and content direction before converting it into the real Laravel and Vue page.</p>',
        buttonText: 'Replay from top',
        buttonUrl: '#top',
    },
};

function sectionByType(sections, type) {
    return sections.find(section => section?.type === type) || {};
}

function setting(section, key, fallback = '') {
    const value = section?.settings?.[key];
    if (value === null || value === undefined) return fallback;
    if (typeof value === 'string' && value.trim() === '') return fallback;
    if (Array.isArray(value) && value.length === 0) return fallback;
    return value;
}

function text(value, fallback = '') {
    if (value === null || value === undefined) return fallback;
    if (typeof value === 'string' && value.trim() === '') return fallback;
    return value;
}

function asArray(value, fallback = []) {
    return Array.isArray(value) && value.length > 0 ? value : fallback;
}

function normalizeStats(items) {
    return items.map((item, index) => {
        const rawNumber = text(item.number, defaults.stats[index]?.number || '0');
        const match = String(rawNumber).match(/^([\d,.]+)(.*)$/);
        const number = text(match?.[1], rawNumber);
        const suffix = text(item.suffix, match?.[2] || defaults.stats[index]?.suffix || '');
        const target = Number.parseInt(String(rawNumber).replace(/[^\d]/g, ''), 10);

        return {
            number,
            suffix,
            label: text(item.label, defaults.stats[index]?.label || ''),
            target: Number.isNaN(target) ? 0 : target,
        };
    });
}

function normalizeLegacyFeature(item, index) {
    return {
        icon: text(item.icon || item.emoji, defaults.promises.items[index]?.icon || 'sparkles'),
        kicker: text(item.kicker, defaults.promises.items[index]?.kicker || 'Promise'),
        title: text(item.title, defaults.promises.items[index]?.title || ''),
        description: text(item.description, defaults.promises.items[index]?.description || ''),
    };
}

export function useAboutPageSections(props) {
    return computed(() => {
        const sections = Array.isArray(props.sections) ? props.sections : [];
        const page = props.page || {};
        const meta = props.meta || {};
        const heroSection = sectionByType(sections, 'hero');
        const statsSection = sectionByType(sections, 'stats');
        const missionSection = sectionByType(sections, 'content');
        const journeySection = sectionByType(sections, 'split');
        const ribbonSection = sectionByType(sections, 'ribbon');
        const promisesSection = sectionByType(sections, 'features');
        const coverageSection = sectionByType(sections, 'coverage');
        const ctaSection = sectionByType(sections, 'cta');

        const missionContent = text(missionSection.content || meta.mission_statement || meta.company_bio, defaults.mission.content);

        return {
            hero: {
                badge: setting(heroSection, 'badge', defaults.hero.badge),
                title: text(heroSection.title || page.title, defaults.hero.title),
                content: text(heroSection.content, defaults.hero.content),
                imageUrl: setting(heroSection, 'image_url', defaults.hero.imageUrl),
                imageAlt: setting(heroSection, 'image_alt', defaults.hero.imageAlt),
                primaryButtonText: setting(heroSection, 'primary_button_text', defaults.hero.primaryButtonText),
                primaryButtonUrl: setting(heroSection, 'primary_button_url', defaults.hero.primaryButtonUrl),
                secondaryButtonText: setting(heroSection, 'secondary_button_text', defaults.hero.secondaryButtonText),
                secondaryButtonUrl: setting(heroSection, 'secondary_button_url', defaults.hero.secondaryButtonUrl),
                panelImageUrl: setting(heroSection, 'panel_image_url', defaults.hero.panelImageUrl),
                panelImageAlt: setting(heroSection, 'panel_image_alt', defaults.hero.panelImageAlt),
                panelTitle: setting(heroSection, 'panel_title', defaults.hero.panelTitle),
                panelText: setting(heroSection, 'panel_text', defaults.hero.panelText),
                sideImageUrl: setting(heroSection, 'side_image_url', defaults.hero.sideImageUrl),
                sideImageAlt: setting(heroSection, 'side_image_alt', defaults.hero.sideImageAlt),
            },
            stats: normalizeStats(asArray(setting(statsSection, 'items'), defaults.stats)),
            mission: {
                kicker: setting(missionSection, 'kicker', defaults.mission.kicker),
                title: text(missionSection.title, defaults.mission.title),
                content: missionContent,
                proofItems: asArray(setting(missionSection, 'proof_items'), defaults.mission.proofItems),
                missionLines: asArray(setting(missionSection, 'mission_lines'), defaults.mission.missionLines),
            },
            journey: {
                kicker: setting(journeySection, 'kicker', defaults.journey.kicker),
                title: text(journeySection.title, defaults.journey.title),
                content: text(journeySection.content, defaults.journey.content),
                imageUrl: setting(journeySection, 'image_url', defaults.journey.imageUrl),
                imageAlt: setting(journeySection, 'image_alt', defaults.journey.imageAlt),
                routeNoteTitle: setting(journeySection, 'route_note_title', defaults.journey.routeNoteTitle),
                routeNoteText: setting(journeySection, 'route_note_text', defaults.journey.routeNoteText),
                items: asArray(setting(journeySection, 'items'), defaults.journey.items),
            },
            ribbon: {
                title: text(ribbonSection.title, defaults.ribbon.title),
                content: text(ribbonSection.content, defaults.ribbon.content),
                backgroundImageUrl: setting(ribbonSection, 'background_image_url', defaults.ribbon.backgroundImageUrl),
                backgroundImageAlt: setting(ribbonSection, 'background_image_alt', defaults.ribbon.backgroundImageAlt),
            },
            promises: {
                kicker: setting(promisesSection, 'kicker', defaults.promises.kicker),
                title: text(promisesSection.title, defaults.promises.title),
                content: text(promisesSection.content, defaults.promises.content),
                items: asArray(setting(promisesSection, 'items'), defaults.promises.items).map(normalizeLegacyFeature),
            },
            coverage: {
                kicker: setting(coverageSection, 'kicker', defaults.coverage.kicker),
                title: text(coverageSection.title, defaults.coverage.title),
                content: text(coverageSection.content, defaults.coverage.content),
                items: asArray(setting(coverageSection, 'items'), defaults.coverage.items).map((item, index) => ({
                    imageUrl: text(item.image_url || item.imageUrl, defaults.coverage.items[index]?.imageUrl || defaults.coverage.items[0].imageUrl),
                    imageAlt: text(item.image_alt || item.imageAlt, defaults.coverage.items[index]?.imageAlt || ''),
                    title: text(item.title, defaults.coverage.items[index]?.title || ''),
                    description: text(item.description, defaults.coverage.items[index]?.description || ''),
                })),
            },
            cta: {
                kicker: setting(ctaSection, 'kicker', defaults.cta.kicker),
                title: text(ctaSection.title, defaults.cta.title),
                content: text(ctaSection.content, defaults.cta.content),
                buttonText: setting(ctaSection, 'button_text', defaults.cta.buttonText),
                buttonUrl: setting(ctaSection, 'button_url', defaults.cta.buttonUrl),
            },
        };
    });
}
