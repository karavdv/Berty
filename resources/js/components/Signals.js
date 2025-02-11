import { signal } from "@preact/signals-react";

// overlapping use
export const selectedCurrency = signal("");
export const errors = signal({});

// analyse signals
export const results = signal([]);
export const loading = signal(false);

// trading bot signals
export const availablePairs = signal([]);
export const selectedPair = signal("");
export const loadingPairs = signal(false);
export const tradeSize = signal("");
export const drop = signal("");
export const profit = signal("");
export const startBuy = signal("");
export const maxBuys = signal("");
export const accumulate = signal(false);
export const topEdge = signal("");
export const stopLoss = signal("");
export const showOverviewPage = signal(false);
