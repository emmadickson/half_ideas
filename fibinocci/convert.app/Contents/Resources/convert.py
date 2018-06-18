def convert():
    with open('static/synonyms_en.txt', 'r') as entriesFile:
        storedEntries = entriesFile.read()

    entriesFile.close()
    storedEntries = storedEntries.split('\n')


    for x in range(0, len(storedEntries)):
        entry = storedEntries[x].split(',')

        print(storedEntries[x])



    with open('static/dict.txt', 'w') as entriesFile:
        entriesFile.write("{")
        for x in range(0, len(storedEntries)):
            entry = storedEntries[x].split(',')
            entriesFile.write("\"")
            entriesFile.write(storedEntries[0])
            entriesFile.write("\":")
            for x in range(1, len(storedEntries)):
                entriesFile.write("\"")
                entriesFile.write(storedEntries[x])
                entriesFile.write("\",")
        entriesFile.write("}")
convert()
