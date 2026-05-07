export const VEHICLE_IMAGE_MAX_FILE_SIZE = 5 * 1024 * 1024;
export const VEHICLE_IMAGE_MIN_WIDTH = 1200;
export const VEHICLE_IMAGE_MIN_HEIGHT = 900;
export const VEHICLE_IMAGE_ACCEPTED_TYPES = ['image/jpeg', 'image/jpg', 'image/png'];
export const VEHICLE_IMAGE_UPLOAD_HINT = 'JPG, PNG up to 5MB each. Use landscape photos at least 1200×900px.';
export const VEHICLE_IMAGE_UPLOAD_DETAIL = 'Portrait or narrow images are rejected. Use width greater than height for cleaner vehicle cards.';

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

        if (dimensions.width < VEHICLE_IMAGE_MIN_WIDTH || dimensions.height < VEHICLE_IMAGE_MIN_HEIGHT) {
            return `"${file.name}" is too small. Use images of at least 1200×900px.`;
        }

        if (dimensions.width <= dimensions.height) {
            return `"${file.name}" is portrait-oriented. Use landscape photos where width is greater than height.`;
        }
    }

    return null;
};
