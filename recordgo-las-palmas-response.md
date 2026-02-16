# Record Go test response (Las Palmas / branch 34902)

Generated from live API calls using credentials in `.env`.

## Requests

### Availability (booking_getAvailability)
```
POST https://api.recordgo.cloud/brokers/booking_getAvailability/
Headers:
  Ocp-Apim-Subscription-Key: (from .env)
Body:
{
  "partnerUser": "vrooem",
  "country": "IC",
  "sellCode": "96",
  "pickupBranch": 34902,
  "dropoffBranch": 34902,
  "pickupDateTime": "2026-05-10T12:30:00",
  "dropoffDateTime": "2026-05-11T12:30:00",
  "driverAge": 30,
  "language": "ES"
}
```

### Associated complements (booking_getAssociatedComplements)
```
POST https://api.recordgo.cloud/brokers/booking_getAssociatedComplements/
Headers:
  Ocp-Apim-Subscription-Key: (from .env)
Body:
{
  "partnerUser": "vrooem",
  "country": "IC",
  "sellCode": "96",
  "pickupBranch": 34902,
  "dropoffBranch": 34902,
  "pickupDateTime": "2026-05-10T12:30:00",
  "dropoffDateTime": "2026-05-11T12:30:00",
  "driverAge": 30,
  "productId": "6",
  "acrissCode": "CDMR",
  "language": "ES"
}
```

## Availability response (summary)

- status: 200 OK
- sellCodeVer: 261
- acriss_count: 18

### ACRISS sample (first item)
```json
{
  "acrissCode": "CDMR",
  "acrissId": 4,
  "acrissSeats": 5,
  "acrissDoors": 5,
  "acrissSuitcase": 2,
  "gearboxType": "Manual",
  "imagesArray": [
    {
      "acrissImgUrl": "http://www.recordrentacar.com/images/cars/Dr_4.0.png",
      "acrissDisplayName": "DR4.0",
      "isDefault": true
    },
    {
      "acrissImgUrl": "http://www.recordrentacar.com/images/cars/citroenc3automatico.png",
      "acrissDisplayName": "Citroën C3",
      "isDefault": false
    }
  ]
}
```

### Product sample (first product under CDMR)

Prices (from availability):
- priceTaxIncDay: 16.54
- priceTaxIncBooking: 16.54
- priceTaxIncBookingDiscount: 16.54
- units: 1 day

Product metadata:
```json
{
  "productId": "6",
  "productVer": 1522,
  "productName": "Go Easy",
  "productSubtitle": null,
  "productDescription": "<p>Producto básico</p>",
  "minAgeProduct": 19,
  "maxAgeProduct": 75
}
```

Included complements (deposit/excess live here):
```json
{
  "complementId": 19,
  "complementName": "Cobertura Basic",
  "complementCategory": "COVERAGE",
  "preauth&Excess": [
    { "type": "Preauth", "value": 1400 },
    { "type": "Excess", "value": 1400 },
    { "type": "ExcessLow", "value": 0 }
  ]
}
```

Notes:
- Deposit / excess is provided in `productComplementsIncluded[].preauth&Excess`.
- Automatic complements are in `productComplementsAutom` (empty in this sample).

## Associated complements response (extras)

Status:
- 200 OK

### productAssociatedComplements (customer-selectable extras)
Examples from response:
```json
[
  {
    "complementId": 11,
    "complementName": "Conductor adicional",
    "complementCategory": "SERVICE",
    "priceTaxIncDay": 10.5,
    "priceTaxIncComplement": 10.5
  },
  {
    "complementId": 126,
    "complementName": "Sillita infantil en el sentido de la marcha",
    "complementCategory": "ITEM",
    "priceTaxIncDay": 25.9,
    "priceTaxIncComplement": 25.9
  },
  {
    "complementId": 85,
    "complementName": "Asistencia en carretera premium",
    "complementCategory": "SERVICE",
    "priceTaxIncDay": 15.9,
    "priceTaxIncComplement": 15.9
  }
]
```

### productAutomaticComplements (auto-applied charges)
Examples from response:
```json
[
  {
    "complementId": 32,
    "complementName": "Día extra",
    "complementCategory": "FEE",
    "priceTaxIncDay": 119.37,
    "priceTaxIncComplement": 119.37
  },
  {
    "complementId": 66,
    "complementName": "Conductor junior",
    "complementCategory": "SERVICE",
    "priceTaxIncDay": 10.95,
    "priceTaxIncComplement": 10.95
  }
]
```

## Key takeaways

- **Plans** = Record Go products returned under each ACRISS (each product is a plan).
- **Extras** = productAssociatedComplements (customer-selectable).
- **Automatic supplements** = productAutomaticComplements (auto-applied).
- **Deposit / Excess** = preauth&Excess array inside productComplementsIncluded.
