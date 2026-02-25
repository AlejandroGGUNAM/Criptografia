# García Gallegos Alejandro
# Hernández Nativitas Sofía Alejandra
# Miranda Galván Erick Santiago
# Pérez Nava Francisco Javier
# Vences Santillán Carlos Eduardo


# Escogemos si vamos a cifrar o descifrar
modo = input("Modo (C = cifrar, D = descifrar): ").strip().upper()
clave = input("Hola, dame una clave: ").upper().replace(" ", "").replace("J", "I")

texto = input("Hola, dame un mensaje: ").upper().replace(" ", "").replace("J", "I")


nueva = ""
for c in clave:
    if c.isalpha() and c not in nueva:
        nueva += c

# Completamos sin j
alf = "ABCDEFGHIKLMNOPQRSTUVWXYZ"
for c in alf:
    if c not in nueva:
        nueva += c

# Creamos la matriz
matriz = []
k = 0
for f in range(5):
    fila = []
    for col in range(5):
        fila.append(nueva[k])
        k += 1
    matriz.append(fila)

print("\nMatriz:")
for f in range(5):
    print(" ".join(matriz[f]))


# mapa de las letras, para lozalizar
pos = {}
for f in range(5):
    for c in range(5):
        pos[matriz[f][c]] = (f, c)


# Para cifrar
if modo == "C":
    
    # Preparamos los pares
    pares = []
    i = 0
    while i < len(texto) - 1:
        a = texto[i]
        b = texto[i + 1]

        if not a.isalpha():
            i += 1
            continue
        if not b.isalpha():
            i += 1
            continue

        if a == b:
            pares.append(a + "X")
            i += 2
        else:
            pares.append(a + b)
            i += 2

    if i == len(texto) - 1 and texto[i].isalpha():
        pares.append(texto[i] + "X")

    print("\nLos pares son:", pares)

    cifrado = ""
    for par in pares:
        a, b = par[0], par[1]
        f1, c1 = pos[a]
        f2, c2 = pos[b]

        if f1 == f2:
            cifrado += matriz[f1][(c1 + 1) % 5]
            cifrado += matriz[f2][(c2 + 1) % 5]
        elif c1 == c2: 
            cifrado += matriz[(f1 + 1) % 5][c1]
            cifrado += matriz[(f2 + 1) % 5][c2]
        else:
            cifrado += matriz[f1][c2]
            cifrado += matriz[f2][c1]

    print("\nEl mensaje cifrado es:", cifrado)

# Para decifrar
elif modo == "D":
    pares = []
    i = 0
    while i < len(texto):
        pares.append(texto[i:i+2])
        i += 2

    print("\nLos pares:", pares)

    descifrado = ""
    for par in pares:
        a, b = par[0], par[1]
        f1, c1 = pos[a]
        f2, c2 = pos[b]

        if f1 == f2:
            descifrado += matriz[f1][(c1 - 1) % 5]
            descifrado += matriz[f2][(c2 - 1) % 5]
        elif c1 == c2: 
            descifrado += matriz[(f1 - 1) % 5][c1]
            descifrado += matriz[(f2 - 1) % 5][c2]
        else:
            descifrado += matriz[f1][c2]
            descifrado += matriz[f2][c1]

    # Hacemos una limpieza quitamos X de relleno entre dobles
    limpio = ""
    j = 0
    while j < len(descifrado):
        if j + 2 < len(descifrado) and descifrado[j] == descifrado[j + 2] and descifrado[j + 1] == "X":
            limpio += descifrado[j]
            j += 2
        else:
            limpio += descifrado[j]
            j += 1

    if limpio.endswith("X"):
        limpio = limpio[:-1]

    print("\nEl mensaje descifrado es:", limpio.lower())

else:
    print("No correcto. Debes de usae C o D")