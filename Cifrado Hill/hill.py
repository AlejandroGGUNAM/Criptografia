import numpy as np
import sys

# Recibimos el mensaje a cifrar
mensaje = "act"

# Lista donde se guardará el mensaje cifrado
nuevo_mensaje = []

# Llave para cifrar
# Es un cadena que se iterará hasta cifrar todo el mensaje
llave = "gybnqkaap" 

# Lista para guardar la llave en ascii
llave_lista = []

# Iteramos sobre la cadena "llave" y encontramos su valor en el alfabeto
# así conocemos cuanto debemos de desplazarnos
# Agregamos ese valor en una lista para usarlo después
for letra in llave:
    # Determinar si es mayúscula o minúscula
    inicio = ord('A') if letra.isupper() else ord('a')
    
    valor_llave = ord(letra) - inicio
    # Descomentar si se quiere saber el valor de cada una
    # print(valor_llave)
    llave_lista.append(valor_llave)

# Impresion de los valores que se desplazarán para cada letra del mensaje
print("llave: ",llave_lista)

#Cifrado
for letra in mensaje:
    
    # Determinar si es mayúscula o minúscula
    inicio = ord('A') if letra.isupper() else ord('a')
    
    # Para descifrar debemos cambiar el + de la llave_lista por un -
    # valor = (ord(letra) - inicio - llave_lista[i])%26 + inicio
    valor = (ord(letra) - inicio )
    
    nuevo_mensaje.append(valor)
    
# Imprimimos el mensaje cifrado en ascii    
print("entrada: ",nuevo_mensaje)

#Convertimos la llave lista en matriz
K=np.array(llave_lista)
K=K.reshape(3, 3)
print("\nMatriz llave:")
print(K)

#Convertimos la lista con el mensaje en un arreglo
M=np.array(nuevo_mensaje)
M=M.reshape(3, 1)

#Multiplicación de matrices para obtener el mensaje cifrado
C=K@M
C=C%26
print("\nArreglo cifrado:")
print(C)

#Imprimir mensaje cifrado
mensaje_cifrado=""
for i in range(C.shape[0]):
    for j in range(C.shape[1]):
        mensaje_cifrado+=chr(C[i, j]+inicio)

print("mensaje cifrado: ",mensaje_cifrado)

# Para calcular la matriz inversa de la llave necesitamos encontrar primero el determinante
# módulo 26
det_K=round(np.linalg.det(K))
det_K%=26
# Se necesita encontrar el inverso multiplicativo módulo 26
# del determinante: det_K*x≡1 mod 26, para encontrar x se tiene la siguiente función
try:
    det_mod26_K=round(pow(int(det_K),-1,26))
    print(det_mod26_K)
except ValueError:
    sys.exit("La matriz llave utilizada no es válida")

# Ahora calculamos la adjunta de la transpuesta
cof = np.zeros((3, 3), dtype=int)
for i in range(3):
    for j in range(3):
        # menor M_ij: quitar fila i y columna j
        inter = np.delete(np.delete(K, i, axis=0), j, axis=1)
        cof[i, j] = ((-1) ** (i + j)) * round(np.linalg.det(inter))

cof%=26
adj=cof.T

#Calculamos la inversa de la martriz K
K_inversa=(det_mod26_K*adj)%26
print("\nLlave inversa:")
print(K_inversa)

#Deciframos el mensaje
decod=(K_inversa@C)%26

#Imprimir mensaje descifrado
mensaje_descifrado=""
for i in range(decod.shape[0]):
    for j in range(decod.shape[1]):
        mensaje_descifrado+=chr(decod[i, j]+inicio)
print("Mensaje descifrado: ",mensaje_descifrado)

