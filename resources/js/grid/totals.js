const PRECISION = 15
const TIE_TOLERANCE = 1e-9

const customFormatters = new Map()

const customFormatter = (js) => {
    if (!customFormatters.has(js)) {
        customFormatters.set(js, new Function(`return (${js})`)())
    }

    return customFormatters.get(js)
}

const roundScaled = (scaled, halfEven) => {
    const floor = Math.floor(scaled)
    const remainder = scaled - floor

    if (Math.abs(remainder - 0.5) > TIE_TOLERANCE) {
        return Math.round(scaled)
    }

    return halfEven && floor % 2 === 0 ? floor : floor + 1
}

const groupDigits = (digits, number) => {
    if (!number.group || number.groupSize <= 0 || digits.length < number.groupSize + number.minGrouping) {
        return digits
    }

    const groups = [digits.slice(-number.groupSize)]
    let rest = digits.slice(0, -number.groupSize)

    while (rest.length > 0) {
        groups.unshift(rest.slice(-number.secondaryGroupSize))
        rest = rest.slice(0, -number.secondaryGroupSize)
    }

    return groups.join(number.group)
}

const formatNumber = (value, number) => {
    const scale = 10 ** number.maxFraction
    const scaled = roundScaled(Number((Math.abs(value) * scale).toPrecision(PRECISION)), number.halfEven)
    const isNegative = value < 0 && scaled > 0
    const integer = String(Math.floor(scaled / scale))
    const fraction = String(scaled % scale)
        .padStart(number.maxFraction, '0')
        .slice(0, number.maxFraction)
        .replace(new RegExp(`0{0,${number.maxFraction - number.minFraction}}$`), '')
    const digits = `${groupDigits(integer, number)}${fraction ? `${number.decimal}${fraction}` : ''}`

    return isNegative ? `${number.negativePrefix}${digits}${number.negativeSuffix}` : `${number.positivePrefix}${digits}${number.positiveSuffix}`
}

export const formatTotal = (value, format) => {
    const text = format.js ? String(customFormatter(format.js)(value)) : formatNumber(value, format.number)

    return `${format.prefix ?? ''}${text}${format.suffix ?? ''}`
}

export const sumTotals = (rows, fields) =>
    Object.fromEntries(
        fields.map((field) => [field, Number(rows.reduce((sum, row) => sum + (row.totals?.[field] ?? 0), 0).toPrecision(PRECISION))]),
    )
