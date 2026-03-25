const sanitizeText = (value) => `${value ?? ''}`.trim();

const resolveDisplayName = (vehicle = {}) => {
    const source = sanitizeText(vehicle?.source).toLowerCase();

    if (source === 'okmobility') {
        return (
            sanitizeText(vehicle?.display_name)
            || sanitizeText(vehicle?.group_description)
            || sanitizeText(vehicle?.model)
            || 'Vehicle'
        );
    }

    return (
        [sanitizeText(vehicle?.brand), sanitizeText(vehicle?.model)]
            .filter(Boolean)
            .join(' ')
        || sanitizeText(vehicle?.group_description)
        || sanitizeText(vehicle?.display_name)
        || 'Vehicle'
    );
};

const escapeSvgText = (value) => sanitizeText(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

const hashString = (value) => {
    let hash = 0;
    const input = sanitizeText(value) || 'vehicle';

    for (let index = 0; index < input.length; index += 1) {
        hash = ((hash << 5) - hash) + input.charCodeAt(index);
        hash |= 0;
    }

    return Math.abs(hash);
};

const paletteForVehicle = (vehicle = {}) => {
    const palettes = [
        ['#123F53', '#2C6E86', '#E8F2F6'],
        ['#4B3426', '#9C6B45', '#F7E7D8'],
        ['#1F3A2D', '#4F8B67', '#E5F3EA'],
        ['#4C243B', '#A05A78', '#F8E7EF'],
        ['#2E3655', '#6B79B8', '#EAEFFC'],
        ['#4B3A14', '#A98A2D', '#FBF3D6'],
    ];

    const key = [
        sanitizeText(vehicle?.source),
        sanitizeText(vehicle?.brand),
        sanitizeText(vehicle?.model),
        sanitizeText(vehicle?.category),
    ].join('|');

    return palettes[hashString(key) % palettes.length];
};

const initialsForVehicle = (vehicle = {}) => {
    const displayName = resolveDisplayName(vehicle);
    const parts = displayName
        .split(/\s+/)
        .map((part) => part.replace(/[^A-Za-z0-9]/g, ''))
        .filter(Boolean);

    if (parts.length >= 2) {
        return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return 'VR';
};

const buildVehiclePlaceholderSvg = (vehicle = {}) => {
    const [primary, secondary, accent] = paletteForVehicle(vehicle);
    const displayName = escapeSvgText(resolveDisplayName(vehicle));
    const category = escapeSvgText(
        sanitizeText(vehicle?.category).toUpperCase()
        || sanitizeText(vehicle?.sipp_code).toUpperCase()
        || 'CAR'
    );
    const transmission = escapeSvgText(
        sanitizeText(vehicle?.transmission)
        || 'Available'
    );
    const initials = escapeSvgText(initialsForVehicle(vehicle));

    return `
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="520" viewBox="0 0 800 520" role="img" aria-label="${displayName}">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="${primary}" />
      <stop offset="100%" stop-color="${secondary}" />
    </linearGradient>
  </defs>
  <rect width="800" height="520" fill="url(#bg)" rx="28" />
  <circle cx="118" cy="96" r="84" fill="rgba(255,255,255,0.08)" />
  <circle cx="706" cy="436" r="116" fill="rgba(255,255,255,0.08)" />
  <rect x="52" y="52" width="160" height="44" rx="22" fill="rgba(255,255,255,0.18)" />
  <text x="132" y="80" text-anchor="middle" font-family="Arial, sans-serif" font-size="22" font-weight="700" fill="${accent}">${category}</text>
  <text x="64" y="210" font-family="Arial, sans-serif" font-size="132" font-weight="800" fill="rgba(255,255,255,0.92)">${initials}</text>
  <text x="64" y="310" font-family="Arial, sans-serif" font-size="44" font-weight="700" fill="#FFFFFF">${displayName}</text>
  <text x="64" y="356" font-family="Arial, sans-serif" font-size="24" font-weight="500" fill="rgba(255,255,255,0.82)">${transmission}</text>
  <path d="M174 395c16-42 54-72 99-72h152c44 0 82 30 99 72h25c23 0 42 19 42 42v7c0 13-10 23-23 23h-19c-2 29-26 53-56 53s-54-24-56-53H264c-2 29-26 53-56 53s-54-24-56-53h-20c-13 0-23-10-23-23v-7c0-23 19-42 42-42h23Zm95 0h164c-10-19-31-32-54-32H323c-24 0-45 13-54 32Zm-61 79c14 0 25-11 25-25s-11-25-25-25-25 11-25 25 11 25 25 25Zm285 0c14 0 25-11 25-25s-11-25-25-25-25 11-25 25 11 25 25 25Z" fill="rgba(255,255,255,0.92)" />
</svg>`.trim();
};

export const buildVehiclePlaceholderDataUri = (vehicle = {}) => {
    const svg = buildVehiclePlaceholderSvg(vehicle);

    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
};

export const resolveVehicleImageSource = (vehicle = {}) => {
    // Top-level image field (set by transformer or factory)
    if (sanitizeText(vehicle?.image)) return vehicle.image;
    if (sanitizeText(vehicle?.image_url)) return vehicle.image_url;
    if (sanitizeText(vehicle?.image_path)) return vehicle.image_path;

    // Images array (internal vehicles via legacy payload)
    if (Array.isArray(vehicle?.images)) {
        const primaryImage = vehicle.images.find((image) => image?.image_type === 'primary')?.image_url;
        if (sanitizeText(primaryImage)) return primaryImage;
    }

    // Booking context images (internal canonical path)
    const providerImages = vehicle?.booking_context?.provider_payload?.images;
    if (Array.isArray(providerImages)) {
        const primaryImage = providerImages.find((image) => image?.image_type === 'primary')?.image_url;
        if (sanitizeText(primaryImage)) return primaryImage;
    }

    return buildVehiclePlaceholderDataUri(vehicle);
};
