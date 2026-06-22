export const VEHICLE_IMAGE_MAX_FILE_SIZE = 5 * 1024 * 1024;
export const VEHICLE_IMAGE_ACCEPTED_TYPES = ['image/jpeg', 'image/jpg', 'image/png'];
export const VEHICLE_IMAGE_UPLOAD_HINT = 'JPG, PNG up to 5MB each. Landscape photos work best, portrait photos are supported.';
export const VEHICLE_IMAGE_UPLOAD_DETAIL = 'Portrait images are centered safely in previews and galleries so the vehicle is not stretched.';

const readImageDimensions = (file) => new Promise((resolve, reject) => {
    const objectUrl = URL.createObjectURL(file);
    const image = new Image();

    image.onload = () => {
        resolve({
            width: image.naturalWidth,
            height: image.naturalHeight,
        });
        URL.revokeObjectURL(objectUrl);
    };

    image.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        reject(new Error('Failed to read image dimensions.'));
    };

    image.src = objectUrl;
});

export const validateVehicleImageFiles = async (files) => {
    for (const file of files) {
        if (!VEHICLE_IMAGE_ACCEPTED_TYPES.includes(file.type)) {
            return 'Please upload only JPG or PNG images.';
        }

        if (file.size > VEHICLE_IMAGE_MAX_FILE_SIZE) {
            return 'Image size must be under 5MB.';
        }

        let dimensions;

        try {
            dimensions = await readImageDimensions(file);
        } catch {
            return `We could not read the dimensions for "${file.name}". Please try another image.`;
        }

        if (dimensions.width < 1 || dimensions.height < 1) {
            return `We could not read a valid image size for "${file.name}". Please try another image.`;
        }
    }

    return null;
};
