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

const containsAny = (haystack, needles) => needles.some((needle) => needle && haystack.includes(needle));

const isSpecialPerformanceProfile = ({ lookup, bodyStyle, horsepower }) => {
  if (["sport", "roadster"].includes(bodyStyle)) return true;

  if (
    containsAny(lookup, [
      "sports car",
      "sport car",
      "performance",
      "supercar",
      "exotic",
      "corvette",
      "huracan",
      "lamborghini",
      "ferrari",
      "mclaren",
      "911",
      "porsche 911",
      "aston martin",
      "amg gt",
      "r8",
      "nissan gt r",
      "gtr",
    ])
  ) {
    return ["convertible", "coupe"].includes(bodyStyle) || (horsepower ?? 0) >= 220;
  }

  return false;
};

const resolveCategoryLetter = ({ categoryName, bodyStyle, seatingCapacity, horsepower, brand, model }) => {
  const category = normalizeText(categoryName);
  const seats = toInt(seatingCapacity) ?? 0;
  const hp = toInt(horsepower) ?? 0;
  const lookup = [category, normalizeText(brand), normalizeText(model)].filter(Boolean).join(" ");

  if (isSpecialPerformanceProfile({ lookup, bodyStyle, horsepower: hp })) return "X";

  if (category && containsAny(category, ["luxury", "prestige"])) return "L";
  if (category?.includes("premium")) return "P";

  if (["van", "minivan"].includes(bodyStyle)) {
    return seats >= 7 ? "S" : "I";
  }

  if (bodyStyle === "suv") {
    if (category && containsAny(category, ["luxury", "prestige"])) return "L";
    if (category?.includes("premium")) return "P";
    if (hp >= 190 || seats >= 7) return "F";
    return "I";
  }

  if (category && containsAny(category, ["mini"])) return "M";
  if (category && containsAny(category, ["city", "economy"])) return "E";
  if (category && containsAny(category, ["compact"])) return "C";
  if (category && containsAny(category, ["intermediate", "mid size", "midsize"])) return "I";
  if (category && containsAny(category, ["standard"])) return "S";
  if (category && containsAny(category, ["full size", "fullsize"])) return "F";

  if (hp >= 220) return "P";
  if (hp >= 170 || seats >= 7) return "F";
  if (hp >= 130) return "I";
  if (hp >= 90) return "C";
  return "E";
};

const resolveTypeLetter = (bodyStyle, numberOfDoors) => {
  const doors = toInt(numberOfDoors) ?? 4;

  switch (bodyStyle) {
    case "sport":
      return "S";
    case "roadster":
      return "N";
    case "convertible":
    case "cabriolet":
      return "T";
    case "coupe":
      return "E";
    case "suv":
      return "F";
    case "wagon":
    case "estate":
      return "W";
    case "van":
    case "minivan":
      return "V";
    case "pickup":
      return "P";
    default:
      return doors >= 4 ? "D" : "C";
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
      return "D";
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
    numberOfDoors: attributes.numberOfDoors,
    horsepower: attributes.horsepower,
    brand: attributes.brand,
    model: attributes.model,
  });
  const typeLetter = resolveTypeLetter(bodyStyle, attributes.numberOfDoors);
  const transmissionLetter = transmission === "automatic" ? "A" : "M";
  const fuelAcLetter = resolveFuelAcLetter(fuel, airConditioning);

  return `${categoryLetter}${typeLetter}${transmissionLetter}${fuelAcLetter}`;
};
