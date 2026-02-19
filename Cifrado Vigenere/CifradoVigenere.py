# Recibimos el mensaje a cifrar
mensaje = input("Introduzca mensaje: ")

# Lista donde se guardará el mensaje cifrado
nuevo_mensaje = []

# Llave para cifrar
# Es un cadena que se iterará hasta cifrar todo el mensaje
llave = "hola" 

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
    
# Inicializamos una variable para poder acceder al indice que necesitemos de la llave
# ya que en caso de no ser de la misma longitud del mensaje, se debe de poder repetir
i = 0

# Impresion de los valores que se desplazarán para cada letra del mensaje
print(llave_lista)

#Cifrado
for letra in mensaje:
    # Si ya recorrimos toda la llave, reinicia el contador
    if i >= len(llave_lista):
        i = 0
    
    # Determinar si es mayúscula o minúscula
    inicio = ord('A') if letra.isupper() else ord('a')
    
    # Para descifrar debemos cambiar el + de la llave_lista por un -
    # valor = (ord(letra) - inicio + llave_lista[i])%25 + inicio
    valor = (ord(letra) - inicio - llave_lista[i])%25 + inicio
    
    nuevo_mensaje.append(valor)
    
    # Incrementamos el contador para acceder al siguiente valor de la llave
    i += 1

# Imprimimos el mensaje cifrado en ascii    
print(nuevo_mensaje)    

# Convertimos el mensaje cifrado en ascii a caracteres para poder leerlo    
nuevo_mensaje = "".join([chr(n) for n in nuevo_mensaje])

# Imprimimos el mensaje cifrado en caracteres    
print(nuevo_mensaje)    