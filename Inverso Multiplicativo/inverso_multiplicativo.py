def euclides_extendido(a, b):
    # Caso base: si b es 0, el MCD es a.
    if b == 0:
        return a, 1, 0

    # Llamada recursiva usando el algoritmo de Euclides.
    gcd, x1, y1 = euclides_extendido(b, a % b)

    # Ajuste de coeficientes para cumplir: a*x + b*y = gcd.
    x = y1
    y = x1 - (a // b) * y1

    return gcd, x, y


def inverso_multiplicativo(e, n):
    # Se obtiene el MCD y el coeficiente asociado a e.
    gcd, x, _ = euclides_extendido(e, n)

    # Si el MCD no es 1, no existe inverso modular.
    if gcd != 1:
        return None

    return x % n


def encontrar_soluciones(e, n, cantidad):
    # Primero se calcula la solución base.
    inverso_base = inverso_multiplicativo(e, n)

    if inverso_base is None:
        return None

    soluciones = []

    # Las soluciones se obtienen sumando múltiplos de n.
    for k in range(cantidad):
        soluciones.append(inverso_base + k * n)

    return soluciones


def pedir_entero(mensaje, minimo=None):
    while True:
        entrada = input(mensaje).strip()

        try:
            valor = int(entrada)

            # Valida que el número cumpla con el mínimo requerido.
            if minimo is not None and valor < minimo:
                print(f"Ingresa un entero mayor o igual a {minimo}.")
                continue

            return valor

        except ValueError:
            print("Entrada no valida. Ingresa un numero entero.")


def main():
    print("Resolver e * b = 1 (mod n)")
    print("-" * 30)

    # Lectura de datos necesarios.
    e = pedir_entero("Ingresa el valor de e: ", minimo=1)
    n = pedir_entero("Ingresa el valor de n: ", minimo=2)
    cantidad = pedir_entero("Cuantos valores de b quieres encontrar?: ", minimo=1)

    soluciones = encontrar_soluciones(e, n, cantidad)

    # Si no hay inverso, no se puede resolver la congruencia.
    if soluciones is None:
        print(
            f"No existe solucion porque e = {e} no tiene inverso multiplicativo modulo n = {n}."
        )
        print("Esto ocurre cuando mcd(e, n) != 1.")
        return

    print(f"\nSe encontraron {len(soluciones)} valores de b que satisfacen:")
    print(f"{e} * b = 1 (mod {n})\n")

    # Muestra cada valor encontrado de b.
    for indice, b in enumerate(soluciones, start=1):
        print(f"{indice}. b = {b}")

    print(f"\nSolucion base modulo {n}: b = {soluciones[0]}")
    print(f"Las demas soluciones se obtienen sumando multiplos de {n}.")


if __name__ == "__main__":
    main()


