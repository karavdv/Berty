export const handleNumberInput = (setter) => (e) => {
    let value = e.target.value.replace(",", "."); // replace comma with dot
    setter(value);
};
