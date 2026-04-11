const normalizeText = (value) => {
  const text = String(value ?? "").trim().toLowerCase();
  return text === "" ? null : text;
};

const normalizeBool = (value) => {
  if (value === true || value === false) return value;
  if (["1", 1, "true", "yes"].includes(value)) return true;
  if (["0", 0, "false", "no"].includes(value)) return false;
  return null;
};

const toInt = (value) => {
  const number = Number(value);
  return Number.isFinite(number) ? number : null;
};

const resolveCategoryLetter = ({ categoryName, bodyStyle, seatingCapacity, horsepower }) => {
  const category = normalizeText(categoryName);
  const seats = toInt(seatingCapacity) ?? 0;
  const hp = toInt(horsepower) ?? 0;

  if (category?.includes("luxury")) return "L";
  if (category?.includes("premium")) return "P";

  if (["van", "minivan"].includes(bodyStyle)) {
    return seats >= 7 ? "S" : "I";
  }

  if (bodyStyle === "suv") {
    if (category?.includes("luxury")) return "L";
    if (hp >= 190 || seats >= 7) return "F";
    return "I";
  }

  if (hp >= 220) return "L";
  if (hp >= 170 || seats >= 7) return "F";
  if (hp >= 130 || seats >= 5) return "I";
  if (hp >= 90) return "C";
  return "E";
};

const resolveTypeLetter = (bodyStyle) => {
  switch (bodyStyle) {
    case "suv":
      return "F";
    case "wagon":
    case "estate":
      return "W";
    case "van":
    case "minivan":
      return "V";
    case "convertible":
    case "cabriolet":
      return "N";
    case "pickup":
      return "P";
    default:
      return "C";
  }
};

const resolveFuelAcLetter = (fuel, airConditioning) => {
  if (!airConditioning) {
    switch (fuel) {
      case "diesel":
        return "Q";
      case "hybrid":
        return "I";
      case "electric":
        return "C";
      default:
        return "N";
    }
  }

  switch (fuel) {
    case "diesel":
      return "Q";
    case "hybrid":
      return "H";
    case "electric":
      return "E";
    default:
      return "R";
  }
};

export const suggestSippCode = (attributes) => {
  const bodyStyle = normalizeText(attributes.bodyStyle);
  const transmission = normalizeText(attributes.transmission);
  const fuel = normalizeText(attributes.fuel);
  const airConditioning = normalizeBool(attributes.airConditioning);

  if (!bodyStyle || !transmission || !fuel || airConditioning === null) {
    return "";
  }

  const categoryLetter = resolveCategoryLetter({
    categoryName: attributes.categoryName,
    bodyStyle,
    seatingCapacity: attributes.seatingCapacity,
    horsepower: attributes.horsepower,
  });
  const typeLetter = resolveTypeLetter(bodyStyle);
  const transmissionLetter = transmission === "automatic" ? "A" : "M";
  const fuelAcLetter = resolveFuelAcLetter(fuel, airConditioning);

  return `${categoryLetter}${typeLetter}${transmissionLetter}${fuelAcLetter}`;
};
