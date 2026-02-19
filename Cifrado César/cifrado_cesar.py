#Mensaje reccibido
mensaje = input("Introduzca mensaje: ")

#Lista para guardar el mensaje cifrado  
nuevo_mensaje = []

#Llave para cifrar o descifrar el mensaje 
#   (para descifrar se usa el valor negatico),
#   es ciclica debido al uso del modulo
#   (en este caso, el usar %25, se repite cada 25)
llave = 3

#Cifrado
#   Recorrer cada letra del mensaje y obtenemos su valor en ASCII,
for letra in mensaje:
   
    # Determinar si es may�scula o min�scula para poder usar el ascii adecuado
    inicio = ord('A') if letra.isupper() else ord('a')
    
    # Restamos el valor de inicio para que el rango de valores sea de 0 a 25, 
    # luego sumamos la llave y aplicamos el modulo para que se mantenga dentro del rango
    # y finalmente sumamos el valor de inicio para volver al rango de caracteres ASCII
    valor = (ord(letra)  - inicio + llave)%26 + inicio

    # Descomentar la siguiente l�nea para ver el valor de cada letra cifrada
    # print(valor)

    # Agregamos el nuevo valor a la lista del nuevo mensaje
    nuevo_mensaje.append(valor)
    
# Imprimir el mensaje cifrado como una lista de valores ASCII
print(nuevo_mensaje)    

# Convertir la lista de valores ASCII a caracteres y los concatenamos para formar el mensaje cifrado    
nuevo_mensaje = "".join([chr(n) for n in nuevo_mensaje])

# Imprimir el mensaje cifrado ahora como texto    
print(nuevo_mensaje)    