import { signal } from "@preact/signals-react";

// Globale state signals
export const results = signal([]);
export const loading = signal(false);
export const errors = signal({});
